<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class SmartPricingAwardService
{
    private const PAGE_SIZE = 100;

    public function forContractor(string $notifyNo, string $contractorCode): array
    {
        $notifyNo = trim($notifyNo);
        $contractorCode = trim($contractorCode);

        if ($notifyNo === '' || $contractorCode === '') {
            throw new RuntimeException('Thiếu TBMT hoặc mã nhà thầu để đối chiếu Smart Pricing.');
        }

        $first = $this->fetchPage($notifyNo, 0);
        $total = max(0, (int) data_get($first, 'page.totalElements', 0));
        $totalPages = max(1, (int) ceil($total / self::PAGE_SIZE));
        $maxPages = max(1, min(100, (int) config('muasamcong.smart_pricing_max_pages', 100)));
        $pagesToFetch = min($totalPages, $maxPages);

        $rows = [];
        $this->mergeMatches($rows, data_get($first, 'page.content', []), $notifyNo, $contractorCode);

        for ($page = 1; $page < $pagesToFetch; $page++) {
            $response = $this->fetchPage($notifyNo, $page);
            $this->mergeMatches($rows, data_get($response, 'page.content', []), $notifyNo, $contractorCode);
        }

        return [
            'items' => array_values($rows),
            'total_source' => $total,
            'pages_fetched' => $pagesToFetch,
            'total_pages' => $totalPages,
            'truncated' => $pagesToFetch < $totalPages,
        ];
    }

    private function fetchPage(string $notifyNo, int $pageNumber): array
    {
        $response = $this->request()->post($this->endpoint(), [[
            'pageSize' => self::PAGE_SIZE,
            'pageNumber' => max(0, $pageNumber),
            'query' => [[
                'index' => 'es-smart-pricing',
                'keyWord' => $notifyNo,
                'keyWordNotMatch' => '',
                'matchType' => 'exact',
                'matchFields' => ['ma_tbmt'],
                'filters' => [
                    ['fieldName' => 'medicines', 'searchType' => 'in', 'fieldValues' => ['0']],
                    ['fieldName' => 'type', 'searchType' => 'in', 'fieldValues' => ['HANG_HOA']],
                    ['fieldName' => 'tab', 'searchType' => 'in', 'fieldValues' => ['THUOC_TAN_DUOC']],
                ],
            ]],
        ]])->throw()->json();

        if (! is_array($response) || ! is_array(data_get($response, 'page.content'))) {
            throw new RuntimeException('Smart Pricing trả về dữ liệu không hợp lệ.');
        }

        return $response;
    }

    private function mergeMatches(array &$rows, mixed $items, string $notifyNo, string $contractorCode): void
    {
        if (! is_array($items)) {
            return;
        }

        foreach ($items as $item) {
            if (! is_array($item) || trim((string) ($item['maTbmt'] ?? '')) !== $notifyNo) {
                continue;
            }

            $winningCodes = array_values(array_filter(array_map(
                static fn (mixed $code): string => is_scalar($code) ? trim((string) $code) : '',
                (array) ($item['winningCode'] ?? [])
            )));

            if (! in_array($contractorCode, $winningCodes, true)) {
                continue;
            }

            $key = $this->businessKey($item, $notifyNo, $contractorCode);
            $rows[$key] ??= $this->normalize($item, $contractorCode, $key);
        }
    }

    private function businessKey(array $item, string $notifyNo, string $contractorCode): string
    {
        return hash('sha256', json_encode([
            'notify_no' => $notifyNo,
            'contractor_code' => $contractorCode,
            'medicine_code' => $this->medicineCode($item),
            'medicine_name' => $this->scalar($item['tenThuoc'] ?? null),
            'active_ingredient' => $this->scalar($item['tenHoatChat'] ?? null),
            'concentration' => $this->scalar($item['nongDo'] ?? null),
            'route' => $this->scalar($item['duongDung'] ?? null),
            'dosage_form' => $this->scalar($item['dangBaoChe'] ?? null),
            'medicine_group' => $this->scalar($item['nhomThuoc'] ?? null),
            'quantity' => $this->numeric($item['soLuong'] ?? null),
            'winning_unit_price' => $this->numeric($item['donGia'] ?? null),
            'decision_no' => $this->scalar($item['soQuyetDinh'] ?? null),
            'decision_date' => $this->scalar($item['ngayBanHanhQuyetDinh'] ?? null),
            'manufacturer' => $this->scalar($item['tenCoSoSanXuat'] ?? null),
            'country' => $this->scalar($item['nuocSanXuat'] ?? null),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
    }

    private function normalize(array $item, string $contractorCode, string $key): array
    {
        $codes = (array) ($item['winningCode'] ?? []);
        $names = (array) ($item['winningName'] ?? []);
        $winnerName = null;

        foreach ($codes as $index => $code) {
            if (is_scalar($code) && trim((string) $code) === $contractorCode) {
                $candidate = $names[$index] ?? $names[0] ?? null;
                $winnerName = is_scalar($candidate) ? trim((string) $candidate) : null;
                break;
            }
        }

        return [
            'source_key' => $key,
            'notify_no' => trim((string) ($item['maTbmt'] ?? '')),
            'contractor_code' => $contractorCode,
            'contractor_name' => $winnerName,
            'medicine_code' => $this->medicineCode($item),
            'medicine_name' => $this->scalar($item['tenThuoc'] ?? null),
            'active_ingredient' => $this->scalar($item['tenHoatChat'] ?? null),
            'concentration' => $this->scalar($item['nongDo'] ?? null),
            'route' => $this->scalar($item['duongDung'] ?? null),
            'dosage_form' => $this->scalar($item['dangBaoChe'] ?? null),
            'uom' => $this->scalar($item['donViTinh'] ?? null),
            'medicine_group' => $this->scalar($item['nhomThuoc'] ?? null),
            'quantity' => $this->numeric($item['soLuong'] ?? null),
            'winning_unit_price' => $this->numeric($item['donGia'] ?? null),
            'decision_no' => $this->scalar($item['soQuyetDinh'] ?? null),
            'decision_date' => $this->scalar($item['ngayBanHanhQuyetDinh'] ?? null),
            'published_at' => $this->scalar($item['ngayDangTaiKqlcnt'] ?? null),
            'manufacturer' => $this->scalar($item['tenCoSoSanXuat'] ?? null),
            'country' => $this->scalar($item['nuocSanXuat'] ?? null),
            'raw_payload' => $item,
        ];
    }

    private function medicineCode(array $item): ?string
    {
        foreach (['medicineCode', 'medicine_code', 'maThuoc', 'ma_thuoc', 'drugCode', 'code'] as $key) {
            $value = $this->scalar($item[$key] ?? null);
            if ($value !== null) {
                return $value;
            }
        }

        return null;
    }

    private function request(): PendingRequest
    {
        return Http::withHeaders([
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => (string) config('muasamcong.origin'),
            'Referer' => (string) config('muasamcong.referers.pricing'),
            'User-Agent' => (string) config('muasamcong.user_agent'),
        ])->withOptions([
            'verify' => app()->environment('production')
                ? true
                : (bool) config('muasamcong.verify_ssl', true),
            'allow_redirects' => false,
        ])->timeout(max(20, min(120, (int) config('muasamcong.timeout', 20))));
    }

    private function endpoint(): string
    {
        $endpoint = (string) config('muasamcong.endpoints.pricing');
        $parts = parse_url($endpoint);
        $allowedHost = (string) config('muasamcong.allowed_host', 'muasamcong.mpi.gov.vn');

        if (! is_array($parts)
            || ($parts['scheme'] ?? null) !== 'https'
            || strcasecmp((string) ($parts['host'] ?? ''), $allowedHost) !== 0) {
            throw new RuntimeException('Endpoint Smart Pricing không được phép.');
        }

        return $endpoint;
    }

    private function scalar(mixed $value): ?string
    {
        return is_scalar($value) && trim((string) $value) !== '' ? trim((string) $value) : null;
    }

    private function numeric(mixed $value): ?float
    {
        return is_numeric($value) ? (float) $value : null;
    }
}
