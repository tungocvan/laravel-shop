<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class PricingTbmtPaginationService
{
    private const MAX_PAGES = 100;

    public function isTbmtKeyword(string $keyword): bool
    {
        return preg_match('/^IB\d{10,}$/i', trim($keyword)) === 1;
    }

    public function loadAll(string $keyword, array $firstResult): array
    {
        if (! $this->isTbmtKeyword($keyword) || ! ($firstResult['success'] ?? false)) {
            return $firstResult;
        }

        $firstItems = is_array($firstResult['data']['items'] ?? null)
            ? $firstResult['data']['items']
            : [];
        $total = max(count($firstItems), (int) ($firstResult['data']['total'] ?? 0));
        $pageSize = $this->pageSize();
        $totalPages = max(1, (int) ceil($total / $pageSize));
        $pagesToFetch = min(self::MAX_PAGES, $totalPages);

        if ($pagesToFetch <= 1) {
            $firstResult['data']['loaded_total'] = count($firstItems);
            $firstResult['data']['total_pages'] = 1;
            $firstResult['data']['capped'] = false;

            return $firstResult;
        }

        $itemsByKey = [];
        $this->mergeItems($itemsByKey, $firstItems);

        for ($page = 1; $page < $pagesToFetch; $page++) {
            $next = $this->fetchPage(trim($keyword), $page);

            if (! ($next['success'] ?? false)) {
                $firstResult['data']['partial'] = true;
                $firstResult['data']['partial_message'] = $next['message'] ?? 'Không thể tải đầy đủ các trang kết quả.';
                break;
            }

            $this->mergeItems($itemsByKey, $next['items'] ?? []);
        }

        $items = array_values($itemsByKey);
        $firstResult['data']['items'] = $items;
        $firstResult['data']['loaded_total'] = count($items);
        $firstResult['data']['total_pages'] = $totalPages;
        $firstResult['data']['capped'] = $totalPages > self::MAX_PAGES;

        return $firstResult;
    }

    private function fetchPage(string $keyword, int $pageNumber): array
    {
        $url = (string) config('muasamcong.endpoints.pricing');
        $origin = (string) config('muasamcong.origin');
        $referer = (string) config('muasamcong.referers.pricing');

        if (! $this->isApprovedUrl($url) || ! $this->isApprovedUrl($origin) || ! $this->isApprovedUrl($referer)) {
            return ['success' => false, 'message' => 'Cấu hình kết nối Mua sắm công không hợp lệ.'];
        }

        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => $origin,
            'Referer' => $referer,
            'User-Agent' => (string) config('muasamcong.user_agent'),
        ];
        $cookie = trim((string) config('muasamcong.session_cookie'));
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        try {
            $response = Http::withHeaders($headers)
                ->withOptions([
                    'verify' => app()->environment('production') ? true : (bool) config('muasamcong.verify_ssl', true),
                    'allow_redirects' => false,
                ])
                ->timeout(max(1, min(120, (int) config('muasamcong.timeout', 20))))
                ->post($url, $this->payload($keyword, $pageNumber));
        } catch (ConnectionException|Throwable $exception) {
            return ['success' => false, 'message' => 'Không thể tải trang tiếp theo từ Cổng Mua sắm công.'];
        }

        if (! $response->successful()) {
            return ['success' => false, 'message' => 'Cổng Mua sắm công trả về HTTP '.$response->status().'.'];
        }

        $page = $response->json('page');
        if (! is_array($page)) {
            $json = $response->json();
            $page = is_array($json) && is_array($json['page'] ?? null) ? $json['page'] : null;
        }

        if (! is_array($page) || ! is_array($page['content'] ?? null)) {
            return ['success' => false, 'message' => 'Dữ liệu trang kết quả không đúng cấu trúc.'];
        }

        return [
            'success' => true,
            'items' => array_values(array_filter($page['content'], fn (mixed $item): bool => is_array($item))),
        ];
    }

    private function payload(string $keyword, int $pageNumber): array
    {
        return [[
            'pageSize' => $this->pageSize(),
            'pageNumber' => max(0, $pageNumber),
            'query' => [[
                'index' => 'es-smart-pricing',
                'keyWord' => $keyword,
                'keyWordNotMatch' => '',
                'matchType' => 'exact',
                'matchFields' => ['ten_thuoc', 'ten_hoat_chat', 'ma_tbmt', 'winning_name'],
                'filters' => [
                    ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
                    ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
                    ['fieldName' => 'tab', 'searchType' => 'in', 'fieldValues' => ['THUOC_TAN_DUOC']],
                ],
            ]],
        ]];
    }

    private function mergeItems(array &$itemsByKey, array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item)) {
                continue;
            }

            $key = is_scalar($item['id'] ?? null) ? trim((string) $item['id']) : '';
            if ($key === '') {
                $key = hash('sha256', json_encode($item, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            }
            $itemsByKey[$key] = $item;
        }
    }

    private function pageSize(): int
    {
        return max(1, min(100, (int) config('muasamcong.page_size', 20)));
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
}
