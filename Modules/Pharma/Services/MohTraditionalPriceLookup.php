<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

final class MohTraditionalPriceLookup
{
    private const URL = 'https://ydct-dichvucong.moh.gov.vn/api/services/app/quanLyGiaThuoc/GetListCongBoPublicPaging';

    public function search(string $keyword, int $page = 1): array
    {
        $keyword = trim($keyword);
        $page = max(1, min(1000, $page));
        if ($keyword === '') {
            return ['total' => 0, 'items' => []];
        }

        return Cache::remember('pharma:moh-traditional:'.hash('sha256', $keyword.'|'.$page), now()->addMinutes(5), function () use ($keyword, $page): array {
            try {
                // Public endpoint only. Do not copy browser cookies, session IDs or XSRF tokens.
                $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(12)->post(self::URL, [
                    'filterAll' => $keyword,
                    'CongBoGiaThuoc' => (object) [],
                    'KichHoat' => true,
                    'skipCount' => ($page - 1) * 15,
                    'maxResultCount' => 15,
                    'sorting' => null,
                ]);
            } catch (\Illuminate\Http\Client\ConnectionException $exception) {
                throw new RuntimeException('Cổng Y Dược cổ truyền không phản hồi.', 0, $exception);
            }

            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload) ||
                ($payload['success'] ?? false) !== true ||
                ! is_array(data_get($payload, 'result.items')) ||
                ! is_numeric(data_get($payload, 'result.totalCount'))) {
                throw new RuntimeException('Cổng Y Dược cổ truyền không trả về dữ liệu hợp lệ.');
            }

            return [
                'total' => max(0, (int) data_get($payload, 'result.totalCount')),
                'items' => array_map(static fn (array $row): array => [
                    'source_id' => (string) ($row['id'] ?? ''),
                    'name' => (string) ($row['tenThuoc'] ?? ''),
                    'registration' => (string) ($row['soDangKy'] ?? ''),
                    'ingredient' => (string) ($row['thanhPhan'] ?? $row['hoatChat'] ?? ''),
                    'strength' => (string) ($row['hamLuong'] ?? ''),
                    'dosage' => (string) ($row['dangBaoChe'] ?? ''),
                    'country' => (string) ($row['nuocSanXuat'] ?? ''),
                    'package' => (string) ($row['quyCachDongGoi'] ?? ''),
                    'unit' => (string) ($row['donViTinh'] ?? ''),
                    'wholesale' => is_numeric($row['giaBanBuon'] ?? null) ? (float) $row['giaBanBuon'] : null,
                    'retail' => is_numeric($row['giaBanLe'] ?? null) ? (float) $row['giaBanLe'] : null,
                    'manufacturer' => (string) ($row['doanhNghiepSanXuat'] ?? ''),
                    'declarant' => (string) ($row['donViKeKhai'] ?? ''),
                    'status' => trim(strip_tags((string) ($row['trangThaiCongBo'] ?? ''))),
                ], array_filter(data_get($payload, 'result.items'), 'is_array')),
            ];
        });
    }
}
