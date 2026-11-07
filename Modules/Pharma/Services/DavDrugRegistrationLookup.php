<?php

namespace Modules\Pharma\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class DavDrugRegistrationLookup
{
    protected function endpoint(): string
    {
        return 'https://dichvucong.dav.gov.vn/api/services/app/soDangKy/GetAllPublicServerPaging';
    }

    protected function cacheNamespace(): string
    {
        return 'dav-registration';
    }

    protected function perPage(): int
    {
        return self::PER_PAGE;
    }

    public const PER_PAGE = 10;

    public const FIELDS = [
        'name' => 'TenThuoc',
        'registration' => 'SoDangKy',
        'ingredient' => 'HoatChatChinh',
        'registrant' => 'TenCongTyDangKy',
        'manufacturer' => 'TenCongTySanXuat',
    ];

    public function pageSize(): int
    {
        return $this->perPage();
    }

    public function search(string $field = 'name', string $keyword = '', int $page = 1): array
    {
        if (! array_key_exists($field, self::FIELDS)) {
            throw new RuntimeException('Unsupported DAV registration search field.');
        }
        $keyword = trim($keyword);
        $page = max(1, min(1000, $page));

        return Cache::remember('pharma:'.$this->cacheNamespace().':v1:'.hash('sha256', $field.'|'.$keyword.'|'.$page), now()->addMinutes(5), function () use ($field, $keyword, $page): array {
            $filters = $keyword === '' ? (object) [] : [self::FIELDS[$field] => $keyword];
            try {
                $response = Http::acceptJson()->asJson()->connectTimeout(5)->timeout(15)
                    ->post($this->endpoint(), [
                        'filterText' => '',
                        'SoDangKyThuoc' => $filters,
                        'KichHoat' => true,
                        'skipCount' => ($page - 1) * $this->perPage(),
                        'maxResultCount' => $this->perPage(),
                        'sorting' => null,
                    ]);
            } catch (ConnectionException $exception) {
                throw new RuntimeException('Không thể kết nối nguồn đăng ký thuốc DAV.', 0, $exception);
            }
            $payload = $response->json();
            if (! $response->successful() || ! is_array($payload)
                || ($payload['success'] ?? false) !== true
                || ! is_array(data_get($payload, 'result.items'))
                || ! is_numeric(data_get($payload, 'result.totalCount'))) {
                throw new RuntimeException('Nguồn đăng ký thuốc DAV không trả về dữ liệu hợp lệ.');
            }
            $items = [];
            foreach (data_get($payload, 'result.items') as $row) {
                if (! is_array($row) || ! is_scalar($row['id'] ?? null)) {
                    continue;
                }
                $items[] = [
                    'source_id' => (string) $row['id'],
                    'name' => (string) ($row['tenThuoc'] ?? ''),
                    'registration' => (string) ($row['soDangKy'] ?? ''),
                    'old_registration' => (string) ($row['soDangKyCu'] ?? ''),
                    'ingredient' => (string) data_get($row, 'thongTinThuocCoBan.hoatChatChinh', ''),
                    'strength' => (string) data_get($row, 'thongTinThuocCoBan.hamLuong', ''),
                    'dosage_form' => (string) data_get($row, 'thongTinThuocCoBan.dangBaoChe', ''),
                    'packaging' => (string) data_get($row, 'thongTinThuocCoBan.dongGoi', ''),
                    'standard' => (string) data_get($row, 'thongTinThuocCoBan.tieuChuan', ''),
                    'shelf_life' => (string) (data_get($row, 'thongTinThuocCoBan.tuoiTho') ?? data_get($row, 'thongTinThuocCoBan.hanDung') ?? ''),
                    'issued_at' => (string) data_get($row, 'thongTinDangKyThuoc.ngayCapSoDangKy', ''),
                    'expires_at' => (string) data_get($row, 'thongTinDangKyThuoc.ngayHetHanSoDangKy', ''),
                    'decision' => (string) data_get($row, 'thongTinDangKyThuoc.soQuyetDinh', ''),
                    'batch' => (string) data_get($row, 'thongTinDangKyThuoc.dotCap', ''),
                    'manufacturer' => (string) data_get($row, 'congTySanXuat.tenCongTySanXuat', ''),
                    'manufacturer_address' => (string) data_get($row, 'congTySanXuat.diaChiSanXuat', ''),
                    'manufacturer_country' => (string) data_get($row, 'congTySanXuat.nuocSanXuat', ''),
                    'registrant' => (string) data_get($row, 'congTyDangKy.tenCongTyDangKy', ''),
                    'registrant_address' => (string) data_get($row, 'congTyDangKy.diaChiDangKy', ''),
                    'registrant_country' => (string) data_get($row, 'congTyDangKy.nuocDangKy', ''),
                    'active' => ($row['isActive'] ?? false) === true,
                    'withdrawn' => ($row['isDaRutSoDangKy'] ?? false) === true,
                ];
            }

            return ['total' => max(0, (int) data_get($payload, 'result.totalCount')), 'items' => $items];
        });
    }

    public function findOnPage(string $field, string $keyword, int $page, string $sourceId): ?array
    {
        foreach ($this->search($field, $keyword, $page)['items'] as $item) {
            if (hash_equals($item['source_id'], $sourceId)) {
                return $item;
            }
        }

        return null;
    }
}
