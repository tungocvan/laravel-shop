<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class HsmtDetailService
{
    public function fetch(string $notifyId, string $processApply = 'LDT'): array
    {
        $notifyId = trim($notifyId);
        $processApply = trim($processApply) ?: 'LDT';

        if ($notifyId === '') {
            throw new RuntimeException('Thiếu notifyId để tải danh mục HSMT.');
        }

        $token = trim((string) config('muasamcong.smart_token'));
        if ($token === '') {
            throw new RuntimeException('Chưa cấu hình MUASAMCONG_SMART_TOKEN để tải HSMT.');
        }

        $response = $this->request()
            ->post($this->endpoint($token), [
                'id' => $notifyId,
                'processApply' => $processApply,
            ])
            ->throw()
            ->json();

        if (! is_array($response)) {
            throw new RuntimeException('Cổng Mua sắm công trả về HSMT không hợp lệ.');
        }

        $form = collect($response['bidoInvBiddingDTO'] ?? [])
            ->first(fn (mixed $row): bool => is_array($row)
                && ($row['formCode'] ?? null) === 'BD.DT.02.1854');

        if (! is_array($form)) {
            throw new RuntimeException('Không tìm thấy biểu mẫu danh mục thuốc BD.DT.02.1854 trong HSMT.');
        }

        $value = json_decode((string) ($form['formValue'] ?? ''), true);
        if (! is_array($value) || ! is_array($value['Table'] ?? null)) {
            throw new RuntimeException('Biểu mẫu danh mục thuốc HSMT không đúng cấu trúc mong đợi.');
        }

        $items = array_values(array_filter(array_map(
            fn (mixed $row): ?array => is_array($row) ? $this->normalizeItem($row) : null,
            $value['Table']
        )));

        $dataTable = collect($response['bidoInvBiddingDTO'] ?? [])
            ->first(fn (mixed $row): bool => is_array($row)
                && ($row['formCode'] ?? null) === 'BD_DATA_TABLE');
        $metadata = is_array($dataTable)
            ? json_decode((string) ($dataTable['formValue'] ?? ''), true)
            : [];

        return [
            'notify_id' => $notifyId,
            'process_apply' => $processApply,
            'total' => count($items),
            'items' => $items,
            'investor_code' => is_array($metadata) ? ($metadata['investorCode'] ?? null) : null,
            'investor_name' => is_array($metadata) ? ($metadata['investorName'] ?? null) : null,
            'procuring_entity_code' => is_array($metadata) ? ($metadata['procuringEntityCode'] ?? null) : null,
            'procuring_entity_name' => is_array($metadata) ? ($metadata['procuringEntityName'] ?? null) : null,
            'form_code' => 'BD.DT.02.1854',
        ];
    }

    private function normalizeItem(array $row): array
    {
        return [
            'id' => $row['id'] ?? null,
            'lot_no' => $row['lotNo'] ?? null,
            'lot_name' => $row['lotName'] ?? null,
            'medicine_code' => $row['medicineCode'] ?? null,
            'medicine_name' => $row['tenThuoc'] ?? null,
            'active_ingredient' => $row['tenHoatChat'] ?? null,
            'concentration' => $row['nongDo'] ?? null,
            'route' => $row['duongDung'] ?? null,
            'dosage_form' => $row['dangBaoChe'] ?? null,
            'uom' => $row['uom'] ?? null,
            'medicine_group' => $row['groupMedicine'] ?? null,
            'quantity' => $row['quantity'] ?? null,
            'price_plan' => $row['pricePlan'] ?? null,
            'lot_price' => $row['lotPrice'] ?? null,
            'lot_estimate_price' => $row['lotEstimatePrice'] ?? null,
            'quantity_add' => $row['quantityAdd'] ?? null,
            'value_add' => $row['valueAdd'] ?? null,
            'percentage_add' => $row['percentageAdd'] ?? null,
            'notify_no' => $row['notifyNo'] ?? null,
            'notify_id' => $row['notifyId'] ?? null,
            'bid_no' => $row['bidNo'] ?? null,
            'plan_no' => $row['planNo'] ?? null,
        ];
    }

    private function request(): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json, text/plain, */*',
            'Content-Type' => 'application/json',
            'Origin' => config('muasamcong.origin'),
            'Referer' => config('muasamcong.referers.kqlcnt'),
            'User-Agent' => config('muasamcong.user_agent'),
        ];

        $cookie = trim((string) config('muasamcong.session_cookie'));
        if ($cookie !== '') {
            $headers['Cookie'] = $cookie;
        }

        return Http::withHeaders($headers)
            ->timeout(max(60, (int) config('muasamcong.timeout', 20)))
            ->withOptions(['verify' => (bool) config('muasamcong.verify_ssl', true)]);
    }

    private function endpoint(string $token): string
    {
        $endpoint = (string) config('muasamcong.endpoints.hsmt_detail');
        $host = parse_url($endpoint, PHP_URL_HOST);
        $scheme = parse_url($endpoint, PHP_URL_SCHEME);

        if ($scheme !== 'https' || ! hash_equals((string) config('muasamcong.allowed_host'), (string) $host)) {
            throw new RuntimeException('Endpoint HSMT không được phép.');
        }

        return $endpoint.'?'.http_build_query(['token' => $token], '', '&', PHP_QUERY_RFC3986);
    }
}
