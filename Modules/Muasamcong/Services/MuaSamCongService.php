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
    public function searchPricing(string $keyword): array
    {
        $result = $this->post(
            (string) config('muasamcong.endpoints.pricing'),
            $this->pricingPayload($keyword),
            false,
            (string) config('muasamcong.referers.pricing')
        );

        return $this->normalizePage($result, 'giá thuốc');
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

    private function pricingPayload(string $keyword): array
    {
        return [[
            'pageSize' => $this->pageSize(),
            'pageNumber' => 0,
            'query' => [[
                'index' => 'es-smart-pricing',
                'keyWord' => $keyword,
                'keyWordNotMatch' => '',
                'matchType' => 'exact',
                'matchFields' => ['ten_thuoc', 'ten_hoat_chat', 'ma_tbmt'],
                'filters' => [
                    ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
                    ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
                    ['fieldName' => 'tab', 'searchType' => 'in', 'fieldValues' => ['THUOC_TAN_DUOC']],
                ],
            ]],
        ]];
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

    private function post(
        string $url,
        array $payload,
        bool $requiresToken,
        string $referer,
        ?string $tokenOverride = null,
        ?string $cookieOverride = null
    ): array {
        if (! filter_var($url, FILTER_VALIDATE_URL)) {
            return $this->error('Endpoint Mua sắm công chưa được cấu hình.');
        }

        $token = trim($tokenOverride ?? (string) config('muasamcong.smart_token'));

        if ($requiresToken && $token === '') {
            return $this->error('Chưa cấu hình MUASAMCONG_SMART_TOKEN.');
        }

        try {
            $request = $this->client($referer, $cookieOverride);

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

    private function client(string $referer, ?string $cookieOverride = null): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => (string) config('muasamcong.origin'),
            'Referer' => $referer,
            'User-Agent' => (string) config('muasamcong.user_agent'),
        ];

        $cookie = trim($cookieOverride ?? (string) config('muasamcong.session_cookie'));

        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        return Http::withHeaders($headers)
            ->withOptions([
                'verify' => (bool) config('muasamcong.verify_ssl', true),
            ])
            ->timeout((int) config('muasamcong.timeout', 20));
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
