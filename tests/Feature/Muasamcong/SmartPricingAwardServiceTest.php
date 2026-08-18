<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Services\SmartPricingAwardService;
use Tests\TestCase;

class SmartPricingAwardServiceTest extends TestCase
{
    public function test_it_paginates_and_keeps_only_rows_for_selected_contractor(): void
    {
        Http::fakeSequence()
            ->push([
                'page' => [
                    'totalElements' => 101,
                    'content' => [
                        [
                            'id' => 'row-1',
                            'maTbmt' => 'IB2600117160',
                            'winningCode' => ['vn0315681994'],
                            'winningName' => ['CÔNG TY TNHH TM DƯỢC PHẨM KHANG TÍN'],
                            'tenThuoc' => 'Thuốc A',
                            'tenHoatChat' => 'Hoạt chất A',
                            'soLuong' => 10,
                            'donGia' => 1200,
                        ],
                        [
                            'id' => 'other-contractor',
                            'maTbmt' => 'IB2600117160',
                            'winningCode' => ['vn0000000001'],
                            'winningName' => ['Nhà thầu khác'],
                            'tenThuoc' => 'Thuốc B',
                        ],
                    ],
                ],
            ], 200)
            ->push([
                'page' => [
                    'totalElements' => 101,
                    'content' => [
                        [
                            'id' => 'row-2',
                            'maTbmt' => 'IB2600117160',
                            'winningCode' => ['vn0315681994'],
                            'winningName' => ['CÔNG TY TNHH TM DƯỢC PHẨM KHANG TÍN'],
                            'tenThuoc' => 'Thuốc C',
                            'soLuong' => 5,
                            'donGia' => 2500,
                        ],
                    ],
                ],
            ], 200);

        $result = app(SmartPricingAwardService::class)
            ->forContractor('IB2600117160', 'vn0315681994');

        $this->assertSame(101, $result['total_source']);
        $this->assertSame(2, $result['pages_fetched']);
        $this->assertFalse($result['truncated']);
        $this->assertCount(2, $result['items']);
        $this->assertSame('CÔNG TY TNHH TM DƯỢC PHẨM KHANG TÍN', $result['items'][0]['contractor_name']);
        $this->assertSame('Thuốc C', $result['items'][1]['medicine_name']);

        Http::assertSentCount(2);
    }

    public function test_it_deduplicates_rows_with_different_source_ids_but_same_business_identity(): void
    {
        $row = [
            'maTbmt' => 'IB2600117160',
            'winningCode' => ['vn0314492345'],
            'winningName' => ['CÔNG TY TNHH INAFO VIỆT NAM'],
            'tenThuoc' => 'Simegaz Chew',
            'tenHoatChat' => 'Magnesi hydroxyd; Nhôm hydroxyd; Simethicon',
            'nongDo' => '200mg + 200mg + 25mg',
            'duongDung' => 'Uống',
            'dangBaoChe' => 'Viên nhai',
            'nhomThuoc' => 'N2',
            'soLuong' => 200000,
            'donGia' => 2898,
            'soQuyetDinh' => '879/QĐ-BVHM',
            'ngayBanHanhQuyetDinh' => '2026-07-08T23:59:59',
        ];

        Http::fake([
            '*' => Http::response([
                'page' => [
                    'totalElements' => 2,
                    'content' => [
                        ['id' => 'source-a', ...$row],
                        ['id' => 'source-b', ...$row],
                    ],
                ],
            ], 200),
        ]);

        $result = app(SmartPricingAwardService::class)
            ->forContractor('IB2600117160', 'vn0314492345');

        $this->assertSame(2, $result['total_source']);
        $this->assertCount(1, $result['items']);
        $this->assertSame('Simegaz Chew', $result['items'][0]['medicine_name']);
        $this->assertSame(200000.0, $result['items'][0]['quantity']);
        $this->assertSame(2898.0, $result['items'][0]['winning_unit_price']);
    }
}
