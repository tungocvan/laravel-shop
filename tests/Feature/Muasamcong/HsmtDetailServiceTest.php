<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Services\HsmtDetailService;
use Tests\TestCase;

class HsmtDetailServiceTest extends TestCase
{
    public function test_hsmt_detail_parses_only_medicine_catalogue_table(): void
    {
        config()->set('muasamcong.smart_token', 'test-token');
        config()->set('muasamcong.session_cookie', 'JSESSIONID=test');
        config()->set('muasamcong.verify_ssl', true);
        config()->set('muasamcong.endpoints.hsmt_detail', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/lcnt_tbmt_hsmt');
        config()->set('muasamcong.referers.kqlcnt', 'https://muasamcong.mpi.gov.vn/web/guest/contractor-selection');

        Http::fake(function ($request) {
            $this->assertSame([
                'id' => '316e2f33-d8fe-4d9a-b020-b7068aa9398d',
                'processApply' => 'LDT',
            ], $request->data());
            $this->assertStringContainsString('token=test-token', $request->url());

            return Http::response([
                'bidoInvBiddingDTO' => [
                    [
                        'formCode' => 'BD.DT.02.1854',
                        'formValue' => json_encode([
                            'Table' => [[
                                'id' => 2600108026,
                                'lotNo' => 'PP2600108026',
                                'lotName' => 'Atropin sulfat',
                                'medicineCode' => '2260330000011.04',
                                'tenHoatChat' => 'Atropin sulfat',
                                'nongDo' => '0,25mg/ml',
                                'duongDung' => 'Tiêm/Tiêm truyền',
                                'dangBaoChe' => 'Thuốc tiêm',
                                'uom' => 'Chai/Lọ/Ống/Túi/Hộp/Gói',
                                'groupMedicine' => 'Nhóm 4',
                                'quantity' => 111573,
                                'pricePlan' => 780,
                                'lotPrice' => 87026940,
                                'notifyNo' => 'IB2600099293',
                            ]],
                        ]),
                    ],
                    [
                        'formCode' => 'BD_DATA_TABLE',
                        'formValue' => json_encode([
                            'investorCode' => 'vn1800271931',
                            'investorName' => 'Trung tâm Y tế khu vực Ô Môn',
                            'procuringEntityCode' => 'vn1800271931',
                            'procuringEntityName' => 'Trung tâm Y tế khu vực Ô Môn',
                        ]),
                    ],
                    [
                        'formCode' => 'BD.MT.02.1220',
                        'formValue' => json_encode(['Table' => [['lotNo' => 'DO-NOT-DUPLICATE']]]),
                    ],
                ],
            ]);
        });

        $result = app(HsmtDetailService::class)->fetch('316e2f33-d8fe-4d9a-b020-b7068aa9398d');

        $this->assertSame(1, $result['total']);
        $this->assertSame('PP2600108026', $result['items'][0]['lot_no']);
        $this->assertSame('Atropin sulfat', $result['items'][0]['active_ingredient']);
        $this->assertSame('Nhóm 4', $result['items'][0]['medicine_group']);
        $this->assertSame(111573, $result['items'][0]['quantity']);
        $this->assertSame(780, $result['items'][0]['price_plan']);
        $this->assertSame('Trung tâm Y tế khu vực Ô Môn', $result['investor_name']);
        Http::assertSentCount(1);
    }

    public function test_hsmt_detail_rejects_unapproved_endpoint(): void
    {
        config()->set('muasamcong.smart_token', 'test-token');
        config()->set('muasamcong.endpoints.hsmt_detail', 'https://example.com/hsmt');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Endpoint HSMT không được phép.');

        app(HsmtDetailService::class)->fetch('notify-id');
    }
}
