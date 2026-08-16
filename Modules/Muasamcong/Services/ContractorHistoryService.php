<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class ContractorHistoryService
{
    public function search(string $contractorCode, ?string $fromDate = null, ?string $toDate = null): array
    {
        $contractorCode = trim($contractorCode);

        if ($contractorCode === '') {
            throw new RuntimeException('Thiếu mã nhà thầu để tra cứu lịch sử.');
        }

        if (blank(config('muasamcong.session_cookie'))) {
            throw new RuntimeException('Chưa cấu hình MUASAMCONG_SESSION_COOKIE cho Personal Page.');
        }

        $rows = [];
        $page = 1;
        $totalPages = 1;
        $maxPages = (int) config('muasamcong.contractor_history_max_pages', 50);

        do {
            $json = $this->request()->post(
                $this->endpoint(),
                $this->payload($contractorCode, $page, $fromDate, $toDate)
            )->throw()->json();

            if (! is_array($json) || ! is_array(data_get($json, 'listBid.content'))) {
                throw new RuntimeException('Cổng Mua sắm công trả về dữ liệu lịch sử nhà thầu không hợp lệ.');
            }

            foreach (data_get($json, 'listBid.content', []) as $row) {
                if (is_array($row) && ($row['notifyNo'] ?? null)) {
                    $rows[$contractorCode.'|'.$row['notifyNo']] = $row;
                }
            }

            $totalPages = max(1, (int) data_get($json, 'listBid.totalPages', 1));
            $page++;
        } while ($page <= $totalPages && $page <= $maxPages);

        $items = array_values($rows);
        usort($items, fn (array $a, array $b): int => strcmp((string) ($b['createdDate'] ?? ''), (string) ($a['createdDate'] ?? '')));

        return [
            'items' => $items,
            'total' => count($items),
            'reported_total' => (int) data_get($json ?? [], 'listBid.totalElements', count($items)),
            'total_pages' => $totalPages,
        ];
    }

    private function payload(string $contractorCode, int $page, ?string $fromDate, ?string $toDate): array
    {
        return [
            'pageSize' => (int) config('muasamcong.contractor_history_page_size', 10),
            'pageNumber' => $page,
            'request' => [
                'bidName' => '',
                'orgCode' => $contractorCode,
                'fromDate' => $this->isoDate($fromDate ?: '2021-01-01'),
                'toDate' => $toDate ? $this->isoDate($toDate, true) : null,
            ],
        ];
    }

    private function isoDate(string $date, bool $endOfDay = false): string
    {
        return $date.'T'.($endOfDay ? '23:59:59.999' : '00:00:00.000').'Z';
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => config('muasamcong.origin'),
            'Referer' => config('muasamcong.referers.contractor_joined_bids'),
            'User-Agent' => config('muasamcong.user_agent'),
            'Cookie' => config('muasamcong.session_cookie'),
        ])->timeout((int) config('muasamcong.timeout', 20))
            ->withOptions(['verify' => (bool) config('muasamcong.verify_ssl', true)]);
    }

    private function endpoint(): string
    {
        $endpoint = (string) config('muasamcong.endpoints.contractor_joined_bids');
        $host = parse_url($endpoint, PHP_URL_HOST);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($scheme !== 'https' || ! hash_equals((string) config('muasamcong.allowed_host'), (string) $host)) {
            throw new RuntimeException('Endpoint lịch sử nhà thầu không được phép.');
        }

        return $endpoint;
    }
}
