<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Tests\TestCase;

class MuasamcongPricingSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_selected_pricing_result_is_persisted_with_full_payload(): void
    {
        $item = $this->sampleItem();

        $report = app(PricingResultSyncService::class)->syncSelected([$item], [$item['id']], 10);

        $this->assertSame(1, $report['inserted']);
        $this->assertSame(0, $report['duplicates']);
        $this->assertDatabaseCount('muasamcong_pricing_results', 1);

        $saved = PricingResult::query()->firstOrFail();

        $this->assertSame($item['id'], $saved->source_id);
        $this->assertSame('Unafen', $saved->ten_thuoc);
        $this->assertSame('Nhóm 2', $saved->nhom_thuoc);
        $this->assertSame(['CÔNG TY CỔ PHẦN DƯỢC PHẨM NAM SƠN - NAMPHACO'], $saved->winning_name);
        $this->assertSame($item['maTbmt'], $saved->raw_payload['maTbmt']);
        $this->assertSame(10, $saved->synced_by);
    }

    public function test_duplicate_source_id_is_not_synchronized_twice(): void
    {
        $item = $this->sampleItem();
        $service = app(PricingResultSyncService::class);

        $first = $service->syncSelected([$item], [$item['id']]);
        $second = $service->syncSelected([$item], [$item['id']]);

        $this->assertSame(1, $first['inserted']);
        $this->assertSame(0, $second['inserted']);
        $this->assertSame(1, $second['duplicates']);
        $this->assertDatabaseCount('muasamcong_pricing_results', 1);
        $this->assertSame([$item['id']], $service->existingSourceIds([$item]));
    }

    private function sampleItem(): array
    {
        return [
            'id' => '1d70016e-2d73-437c-ad5f-f4af12eb36ef',
            'type' => 'HANG_HOA',
            'tab' => 'THUOC_TAN_DUOC',
            'donViTinh' => 'Chai',
            'maTbmt' => 'IB2500029154',
            'tenCdtBmt' => 'Bệnh viện Đa khoa khu vực miền núi phía Bắc Quảng Nam',
            'maCdt' => 'vn4000349126',
            'winningCode' => ['vn0402010696'],
            'winningName' => ['CÔNG TY CỔ PHẦN DƯỢC PHẨM NAM SƠN - NAMPHACO'],
            'bidForm' => 'DTRR',
            'medicines' => '0',
            'ngayDangTaiKqlcnt' => '2025-06-03T17:42:16',
            'diaDiem' => [[
                'provCode' => '503',
                'provName' => 'Tỉnh Quảng Nam',
                'districtCode' => '50307',
                'districtName' => 'Huyện Đại Lộc',
            ]],
            'donGia' => 95000.0,
            'tenThuoc' => 'Unafen',
            'tenHoatChat' => 'Ibuprofen',
            'nongDo' => '2000mg/100ml - 100ml',
            'duongDung' => 'Uống',
            'dangBaoChe' => 'Hỗn dịch uống',
            'hanDung' => '36 tháng',
            'tenCoSoSanXuat' => 'Gracure Pharmaceuticals Ltd.',
            'nuocSanXuat' => 'India',
            'quyCachDongGoi' => 'Hộp 1 chai x 100ml',
            'soLuong' => 1000.0,
            'nhomThuoc' => 'Nhóm 2',
            'soNhaThauThamDu' => 1.414306289006241,
            'soQuyetDinh' => 'KQ2500029154_2506031633',
            'ngayBanHanhQuyetDinh' => '2025-06-03T23:59:59',
            'gdklh_GPNK' => '890110029925 (VN-21873-19)',
        ];
    }
}
