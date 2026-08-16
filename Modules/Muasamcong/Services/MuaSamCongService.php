<?php

namespace Modules\Muasamcong\Services;

use Carbon\CarbonImmutable;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class MuaSamCongService
{
    private const COMPANY_SEARCH_MAX_PAGES = 25;

    public function searchPricing(string $keyword): array
    {
        $keyword = trim($keyword);
        $medicineBaseName = $this->medicineBaseName($keyword);

        $primary = $this->normalizePage(
            $this->pricingRequest($keyword, 'exact'),
            'giá thuốc'
        );

        if (($primary['success'] ?? false)
            && (int) ($primary['data']['total'] ?? 0) > 0) {
            if ($this->looksLikeWinningCompanySearch($primary['data']['items'] ?? [], $keyword)) {
                return $this->searchCompanyPricing($keyword, $primary);
            }

            return $primary;
        }

        if (! ($primary['success'] ?? false)
            && (int) ($primary['status'] ?? 0) !== 400) {
            return $primary;
        }

        if ($medicineBaseName === null) {
            return $primary;
        }

        $variants = array_values(array_unique(array_filter([
            preg_replace('/(?<=\d),(?=\d)/u', '.', $keyword) ?? $keyword,
            $medicineBaseName,
        ], static fn (string $value): bool => $value !== '')));

        foreach ($variants as $variant) {
            if ($variant === $keyword) {
                continue;
            }

            $candidate = $this->normalizePage(
                $this->pricingRequest($variant, 'exact'),
                'giá thuốc'
            );

            if (! ($candidate['success'] ?? false)) {
                if ((int) ($candidate['status'] ?? 0) === 400) {
                    continue;
                }

                return $candidate;
            }

            if ((int) ($candidate['data']['total'] ?? 0) === 0) {
                continue;
            }

            $filtered = $this->filterEquivalentMedicineNames($candidate, $keyword, true);

            if ((int) ($filtered['data']['total'] ?? 0) > 0) {
                return $filtered;
            }
        }

        $fallback = $this->normalizePage(
            $this->pricingRequest($medicineBaseName, 'any-0'),
            'giá thuốc'
        );

        if (! ($fallback['success'] ?? false)) {
            return $fallback;
        }

        return $this->filterEquivalentMedicineNames($fallback, $keyword, true);
    }

    public function searchHsmt(string $keyword, string $fromDate, string $toDate): array
    {
        $result = $this->post(
            (string) config('muasamcong.endpoints.contractor_search'),
            $this->hsmtPayload($keyword, $fromDate, $toDate),
            true,
            (string) config('muasamcong.referers.portal')
        );

        return $this->normalizePage($result, 'hồ sơ mời thầu');
    }

    public function testSmartToken(?string $token = null, ?string $cookie = null): array
    {
        $result = $this->post(
            (string) config('muasamcong.endpoints.contractor_search'),
            $this->hsmtPayload(
                'thuốc',
                now()->subDays(7)->toDateString(),
                now()->toDateString()
            ),
            true,
            (string) config('muasamcong.referers.portal'),
            $token,
            $cookie
        );

        return $this->normalizePage($result, 'hồ sơ mời thầu');
    }

    public function exportRows(array $results, array $selected): array
    {
        $selected = array_fill_keys(
            array_filter($selected, fn (mixed $value): bool => is_string($value) && $value !== ''),
            true
        );

        return collect($results)
            ->filter(fn (mixed $item): bool => is_array($item)
                && isset($selected[(string) ($item['notifyNo'] ?? '')]))
            ->map(fn (array $item): array => [
                'Tên gói thầu' => $this->firstString($item['bidName'] ?? null),
                'Mã TBMT' => $this->stringValue($item['notifyNo'] ?? null),
                'Ngày đăng tải' => $this->stringValue($item['publicDate'] ?? null),
                'Đóng thầu' => $this->stringValue($item['bidOpenDate'] ?? null),
                'Bên mời thầu' => $this->stringValue($item['investorName'] ?? null),
                'Tỉnh' => $this->locationValue($item, 'provName'),
            ])
            ->values()
            ->all();
    }

    private function searchCompanyPricing(string $keyword, array $primary): array
    {
        $strategies = [
            $this->pricingFilters(),
            [
                ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
                ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
            ],
            [
                ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
            ],
            [],
        ];

        $itemsById = [];
        $this->mergeCompanyItems($itemsById, $primary['data']['items'] ?? [], $keyword);
        $hadSuccessfulExpandedQuery = false;

        foreach ($strategies as $filters) {
            $first = $this->normalizePage(
                $this->pricingRequest($keyword, 'exact', 0, ['winning_name'], $filters),
                'giá thuốc'
            );

            if (! ($first['success'] ?? false)) {
                if ((int) ($first['status'] ?? 0) === 400) {
                    continue;
                }

                return $first;
            }

            $hadSuccessfulExpandedQuery = true;
            $this->mergeCompanyItems($itemsById, $first['data']['items'] ?? [], $keyword);

            $total = max(0, (int) ($first['data']['total'] ?? 0));
            $pageSize = $this->pageSize();
            $totalPages = $pageSize > 0 ? (int) ceil($total / $pageSize) : 1;
            $pagesToFetch = min(self::COMPANY_SEARCH_MAX_PAGES, max(1, $totalPages));

            for ($page = 1; $page < $pagesToFetch; $page++) {
                $next = $this->normalizePage(
                    $this->pricingRequest($keyword, 'exact', $page, ['winning_name'], $filters),
                    'giá thuốc'
                );

                if (! ($next['success'] ?? false)) {
                    break;
                }

                $this->mergeCompanyItems($itemsById, $next['data']['items'] ?? [], $keyword);
            }
        }

        if ($itemsById === [] && $hadSuccessfulExpandedQuery) {
            foreach ($strategies as $filters) {
                $fallback = $this->normalizePage(
                    $this->pricingRequest($keyword, 'any-0', 0, ['winning_name'], $filters),
                    'giá thuốc'
                );

                if (! ($fallback['success'] ?? false)) {
                    continue;
                }

                $this->mergeCompanyItems($itemsById, $fallback['data']['items'] ?? [], $keyword);
            }
        }

        $items = array_values($itemsById);
        usort($items, function (array $left, array $right): int {
            $leftDate = (string) ($left['ngayBanHanhQuyetDinh'] ?? $left['ngayDangTaiKqlcnt'] ?? '');
            $rightDate = (string) ($right['ngayBanHanhQuyetDinh'] ?? $right['ngayDangTaiKqlcnt'] ?? '');

            return strcmp($rightDate, $leftDate);
        });

        return [
            'success' => true,
            'status' => 200,
            'data' => [
                'total' => count($items),
                'items' => $items,
                'expanded_company_search' => true,
                'capped' => count($items) >= self::COMPANY_SEARCH_MAX_PAGES * $this->pageSize(),
            ],
            'message' => null,
        ];
    }

    private function mergeCompanyItems(array &$itemsById, mixed $items, string $keyword): void
    {
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! is_array($item) || ! $this->winningNameMatches($item, $keyword) || ! $this->looksLikeMedicineRow($item)) {
                continue;
            }

            $id = is_scalar($item['id'] ?? null) ? trim((string) $item['id']) : '';
            if ($id === '') {
                $id = hash('sha256', json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }

            $itemsById[$id] = $item;
        }
    }

    private function looksLikeWinningCompanySearch(mixed $items, string $keyword): bool
    {
        if (! is_array($items)) {
            return false;
        }

        foreach ($items as $item) {
            if (is_array($item) && $this->winningNameMatches($item, $keyword)) {
                return true;
            }
        }

        return false;
    }

    private function winningNameMatches(array $item, string $keyword): bool
    {
        $needle = $this->normalizedSearchText($keyword);
        if ($needle === '') {
            return false;
        }

        $names = is_array($item['winningName'] ?? null) ? $item['winningName'] : [];

        foreach ($names as $name) {
            if (! is_scalar($name)) {
                continue;
            }

            $haystack = $this->normalizedSearchText((string) $name);
            if ($haystack !== '' && str_contains($haystack, $needle)) {
                return true;
            }
        }

        return false;
    }

    private function looksLikeMedicineRow(array $item): bool
    {
        return trim((string) ($item['tenThuoc'] ?? '')) !== ''
            || trim((string) ($item['tenHoatChat'] ?? '')) !== ''
            || (($item['tab'] ?? null) === 'THUOC_TAN_DUOC');
    }

    private function pricingRequest(
        string $keyword,
        string $matchType,
        int $pageNumber = 0,
        ?array $matchFields = null,
        ?array $filters = null
    ): array {
        return $this->post(
            (string) config('muasamcong.endpoints.pricing'),
            $this->pricingPayload($keyword, $matchType, $pageNumber, $matchFields, $filters),
            false,
            (string) config('muasamcong.referers.pricing')
        );
    }

    private function pricingPayload(
        string $keyword,
        string $matchType = 'exact',
        int $pageNumber = 0,
        ?array $matchFields = null,
        ?array $filters = null
    ): array {
        return [[
            'pageSize' => $this->pageSize(),
            'pageNumber' => max(0, $pageNumber),
            'query' => [[
                'index' => 'es-smart-pricing',
                'keyWord' => $keyword,
                'keyWordNotMatch' => '',
                'matchType' => $matchType,
                'matchFields' => $matchFields ?? ['ten_thuoc', 'ten_hoat_chat', 'ma_tbmt', 'winning_name'],
                'filters' => $filters ?? $this->pricingFilters(),
            ]],
        ]];
    }

    private function pricingFilters(): array
    {
        return [
            ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
            ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
            ['fieldName' => 'tab', 'searchType' => 'in', 'fieldValues' => ['THUOC_TAN_DUOC']],
        ];
    }

    private function hsmtPayload(string $keyword, string $fromDate, string $toDate): array
    {
        return [[
            'pageSize' => $this->pageSize(),
            'pageNumber' => 0,
            'query' => [[
                'index' => 'es-contractor-selection',
                'keyWord' => $keyword,
                'matchType' => 'any-0',
                'matchFields' => ['notifyNo', 'bidName'],
                'filters' => [
                    [
                        'fieldName' => 'publicDate',
                        'searchType' => 'range',
                        'from' => CarbonImmutable::parse($fromDate)->startOfDay()->toISOString(),
                        'to' => CarbonImmutable::parse($toDate)->endOfDay()->toISOString(),
                    ],
                    ['fieldName' => 'isDomestic', 'searchType' => 'in', 'fieldValues' => [1]],
                    ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['es-notify-contractor']],
                ],
            ]],
        ]];
    }

    private function medicineBaseName(string $keyword): ?string
    {
        $normalized = trim(preg_replace('/(?<=\d),(?=\d)/u', '.', $keyword) ?? $keyword);

        if (preg_match('/^(.*?)[\s-]+\d+(?:\.\d+)?(?:\s*[a-zA-Z%]+)?$/u', $normalized, $matches) !== 1) {
            return null;
        }

        $base = trim((string) ($matches[1] ?? ''));

        return mb_strlen($base) >= 2 ? $base : null;
    }

    private function filterEquivalentMedicineNames(array $result, string $originalKeyword, bool $fallback): array
    {
        $items = is_array($result['data']['items'] ?? null)
            ? $result['data']['items']
            : [];
        $needle = $this->normalizedMedicineName($originalKeyword);

        $equivalent = $needle === ''
            ? []
            : array_values(array_filter(
                $items,
                fn (mixed $item): bool => is_array($item)
                    && $this->normalizedMedicineName((string) ($item['tenThuoc'] ?? '')) === $needle
            ));

        $result['data']['items'] = $equivalent;
        $result['data']['total'] = count($equivalent);

        if ($fallback) {
            $result['data']['fallback'] = true;
        }

        return $result;
    }

    private function post(
        string $url,
        array $payload,
        bool $requiresToken,
        string $referer,
        ?string $tokenOverride = null,
        ?string $cookieOverride = null
    ): array {
        $origin = (string) config('muasamcong.origin');

        if (! $this->isApprovedUrl($url)
            || ! $this->isApprovedUrl($referer)
            || ! $this->isApprovedUrl($origin)) {
            return $this->error('Cấu hình kết nối Mua sắm công không hợp lệ.', 500);
        }

        $token = trim($tokenOverride ?? (string) config('muasamcong.smart_token'));

        if ($requiresToken && $token === '') {
            return $this->error('Chưa cấu hình MUASAMCONG_SMART_TOKEN.');
        }

        try {
            $request = $this->client($origin, $referer, $cookieOverride);

            if ($requiresToken) {
                $request = $request->withQueryParameters(['token' => $token]);
            }

            $response = $request->post($url, $payload);
        } catch (ConnectionException $exception) {
            Log::warning('Không thể kết nối Cổng Mua sắm công.', [
                'host' => parse_url($url, PHP_URL_HOST),
                'exception' => $exception::class,
            ]);

            return $this->error('Không thể kết nối Cổng Mua sắm công. Vui lòng thử lại sau.', 503);
        } catch (Throwable $exception) {
            Log::error('Lỗi khi gọi Cổng Mua sắm công.', [
                'host' => parse_url($url, PHP_URL_HOST),
                'exception' => $exception::class,
            ]);

            return $this->error('Có lỗi xảy ra khi gọi Cổng Mua sắm công.', 502);
        }

        if (! $response->successful()) {
            return $this->error(
                'Cổng Mua sắm công trả về lỗi HTTP '.$response->status().'.',
                $response->status()
            );
        }

        $data = $response->json();

        if (! is_array($data)) {
            return $this->error('Cổng Mua sắm công trả về dữ liệu không hợp lệ.', $response->status());
        }

        return [
            'success' => true,
            'status' => $response->status(),
            'data' => $data,
            'message' => null,
        ];
    }

    private function normalizePage(array $result, string $resource): array
    {
        if (! ($result['success'] ?? false)) {
            return $result;
        }

        $page = $result['data']['page'] ?? null;

        if (! is_array($page) || ! is_array($page['content'] ?? null)) {
            return $this->error(
                'Cổng Mua sắm công trả về cấu trúc '.$resource.' không hợp lệ.',
                502
            );
        }

        $content = array_values(array_filter(
            $page['content'],
            fn (mixed $item): bool => is_array($item)
        ));

        return [
            'success' => true,
            'status' => (int) ($result['status'] ?? 200),
            'data' => [
                'total' => max(0, (int) ($page['totalElements'] ?? count($content))),
                'items' => $content,
            ],
            'message' => null,
        ];
    }

    private function normalizedMedicineName(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = str_replace(',', '.', $value);

        return preg_replace('/[^a-z0-9]+/', '', $value) ?? '';
    }

    private function normalizedSearchText(string $value): string
    {
        $value = Str::lower(Str::ascii(trim($value)));
        $value = preg_replace('/[^a-z0-9]+/', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/', ' ', $value) ?? $value);
    }

    private function pageSize(): int
    {
        return max(1, min(100, (int) config('muasamcong.page_size', 20)));
    }

    private function firstString(mixed $value): string
    {
        if (is_array($value)) {
            return $this->stringValue($value[0] ?? null);
        }

        return $this->stringValue($value);
    }

    private function stringValue(mixed $value): string
    {
        return is_scalar($value) ? Str::limit(trim((string) $value), 32767, '') : '';
    }

    private function locationValue(array $item, string $key): string
    {
        $locations = $item['locations'] ?? null;

        return is_array($locations) && is_array($locations[0] ?? null)
            ? $this->stringValue($locations[0][$key] ?? null)
            : '';
    }

    private function client(string $origin, string $referer, ?string $cookieOverride = null): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => $origin,
            'Referer' => $referer,
            'User-Agent' => (string) config('muasamcong.user_agent'),
        ];

        $cookie = trim($cookieOverride ?? (string) config('muasamcong.session_cookie'));

        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        return Http::withHeaders($headers)
            ->withOptions([
                'verify' => app()->environment('production')
                    ? true
                    : (bool) config('muasamcong.verify_ssl', true),
                'allow_redirects' => false,
            ])
            ->timeout(max(1, min(120, (int) config('muasamcong.timeout', 20))));
    }

    private function isApprovedUrl(string $url): bool
    {
        $parts = parse_url($url);
        $allowedHost = (string) config('muasamcong.allowed_host', 'muasamcong.mpi.gov.vn');

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && strcasecmp((string) ($parts['host'] ?? ''), $allowedHost) === 0
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && (! isset($parts['port']) || (int) $parts['port'] === 443);
    }

    private function error(string $message, int $status = 0): array
    {
        return [
            'success' => false,
            'status' => $status,
            'data' => null,
            'message' => $message,
        ];
    }
}
