<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Services\KqlcntService;
use Tests\TestCase;

class KqlcntServiceTest extends TestCase
{
    public function test_kqlcnt_filters_contracts_to_selected_contractor_and_does_not_guess_lots(): void
    {
        config()->set('muasamcong.smart_token', 'test-token');
        config()->set('muasamcong.session_cookie', 'JSESSIONID=test');
        config()->set('muasamcong.verify_ssl', true);
        config()->set('muasamcong.endpoints.contractor_search', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/smart/search');
        config()->set('muasamcong.endpoints.kqlcnt_tbmt_detail', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/lcnt_tbmt_ttc_ldt');
        config()->set('muasamcong.endpoints.kqlcnt_contracts', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/econsign/contract-info/list-contract-for-po');
        config()->set('muasamcong.referers.portal', 'https://muasamcong.mpi.gov.vn/');
        config()->set('muasamcong.referers.kqlcnt', 'https://muasamcong.mpi.gov.vn/web/guest/contractor-selection');

        Http::fake(function ($request) {
            $url = $request->url();

            if (str_contains($url, '/services/smart/search')) {
                return Http::response([
                    'page' => [
                        'content' => [[
                            'id' => '3a97ae93-ac61-4644-a5bd-371cbceab4ac',
                            'notifyNo' => 'IB2500539527',
                            'bidName' => 'Gói Thầu Thuốc Generic',
                        ]],
                        'totalElements' => 1,
                    ],
                ]);
            }

            if (str_contains($url, 'lcnt_tbmt_ttc_ldt')) {
                $this->assertSame(['id' => '3a97ae93-ac61-4644-a5bd-371cbceab4ac'], $request->data());

                return Http::response([
                    'bidoNotifyContractorM' => [
                        'id' => '3a97ae93-ac61-4644-a5bd-371cbceab4ac',
                        'notifyNo' => 'IB2500539527',
                        'bidName' => 'Gói Thầu Thuốc Generic',
                        'isMedicine' => 1,
                        'bidId' => 'bid-1',
                        'investorCode' => 'vn2000269822',
                        'investorName' => 'Sở Y tế Cà Mau',
                    ],
                    'bidoBidStatus' => [
                        'status' => 'PUB_KQLCNT',
                        'published' => 1,
                        'bidId' => 'bid-1',
                    ],
                ]);
            }

            $this->assertStringContainsString('list-contract-for-po', $url);
            $this->assertSame(['notifyNo' => 'IB2500539527'], $request->data());

            return Http::response([
                [
                    'contractNo' => '73/2026/SYT-CM',
                    'contractorPassList' => json_encode([[
                        'contractorCode' => 'vn4401112861',
                        'contractorName' => 'CÔNG TY FP',
                    ]]),
                    'lotResultDTO' => json_encode([[
                        'listTablePrice' => [],
                    ]]),
                ],
                [
                    'contractNo' => '231/2026/SYT-CM',
                    'contractorPassList' => json_encode([[
                        'contractorCode' => 'vn0304819721',
                        'contractorName' => 'CÔNG TY ĐỨC ANH',
                    ]]),
                    'lotResultDTO' => json_encode([[
                        'listTablePrice' => [],
                    ]]),
                ],
            ]);
        });

        $result = app(KqlcntService::class)->resolveByNotifyNo('IB2500539527', 'vn4401112861');

        $this->assertSame('PUB_KQLCNT', $result['status']);
        $this->assertTrue($result['published']);
        $this->assertSame('vn2000269822', $result['investor_code']);
        $this->assertSame('Sở Y tế Cà Mau', $result['investor_name']);
        $this->assertCount(1, $result['contracts']);
        $this->assertCount(2, $result['all_winners']);
        $this->assertSame('73/2026/SYT-CM', $result['contracts'][0]['contractNo']);
        $this->assertSame('CÔNG TY FP', $result['contracts'][0]['contractorPassListParsed'][0]['contractorName']);
        $this->assertSame([], $result['verified_lots']);
        Http::assertSentCount(3);
    }

    public function test_kqlcnt_only_exposes_lot_when_source_links_lot_to_contractor(): void
    {
        config()->set('muasamcong.smart_token', 'test-token');
        config()->set('muasamcong.verify_ssl', true);
        config()->set('muasamcong.endpoints.kqlcnt_tbmt_detail', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/lcnt_tbmt_ttc_ldt');
        config()->set('muasamcong.endpoints.kqlcnt_contracts', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/econsign/contract-info/list-contract-for-po');
        config()->set('muasamcong.referers.kqlcnt', 'https://muasamcong.mpi.gov.vn/web/guest/contractor-selection');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'lcnt_tbmt_ttc_ldt')) {
                return Http::response([
                    'bidoNotifyContractorM' => ['bidName' => 'Gói thuốc', 'isMedicine' => 1],
                    'bidoBidStatus' => ['status' => 'PUB_KQLCNT', 'published' => 1],
                ]);
            }

            return Http::response([[
                'contractNo' => 'HD-01',
                'contractorPassList' => json_encode([[
                    'contractorCode' => 'vn4401112861',
                    'contractorName' => 'CÔNG TY FP',
                ]]),
                'lotResultDTO' => json_encode([[
                    'listTablePrice' => [[
                        'lotNo' => 'PP2500561639',
                        'lotName' => 'Acarbose',
                        'contractorCode' => 'vn4401112861',
                        'unitPrice' => 1000,
                    ]],
                ]]),
            ]]);
        });

        $result = app(KqlcntService::class)->resolve(
            '3a97ae93-ac61-4644-a5bd-371cbceab4ac',
            'IB2500539527',
            'vn4401112861'
        );

        $this->assertCount(1, $result['verified_lots']);
        $this->assertSame('PP2500561639', $result['verified_lots'][0]['lotNo']);
    }

    public function test_kqlcnt_reads_winner_from_direct_contract_fields_when_pass_list_is_null(): void
    {
        config()->set('muasamcong.smart_token', 'test-token');
        config()->set('muasamcong.verify_ssl', true);
        config()->set('muasamcong.endpoints.kqlcnt_tbmt_detail', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/lcnt_tbmt_ttc_ldt');
        config()->set('muasamcong.endpoints.kqlcnt_contracts', 'https://muasamcong.mpi.gov.vn/o/egp-portal-contractor-selection-v2/services/econsign/contract-info/list-contract-for-po');
        config()->set('muasamcong.referers.kqlcnt', 'https://muasamcong.mpi.gov.vn/web/guest/contractor-selection');

        Http::fake(function ($request) {
            if (str_contains($request->url(), 'lcnt_tbmt_ttc_ldt')) {
                return Http::response([
                    'bidoNotifyContractorM' => [
                        'bidName' => 'Gói thuốc Generic',
                        'isMedicine' => 1,
                        'investorName' => 'Bệnh viện A',
                    ],
                    'bidoBidStatus' => ['status' => 'PUB_KQLCNT', 'published' => 1],
                ]);
            }

            return Http::response([
                [
                    'contractNo' => '218/HĐNT',
                    'contractorCode' => 'vn0402010696',
                    'contractorName' => 'CÔNG TY CỔ PHẦN DƯỢC PHẨM NAM SƠN - NAMPHACO',
                    'contractorPassList' => null,
                    'lotResultDTO' => json_encode([['listTablePrice' => []]]),
                ],
                [
                    'contractNo' => '219/HĐNT',
                    'contractorCode' => 'vn0309829522',
                    'contractorName' => 'CÔNG TY CỔ PHẦN GONSA',
                    'contractorPassList' => null,
                    'lotResultDTO' => json_encode([['listTablePrice' => []]]),
                ],
            ]);
        });

        $result = app(KqlcntService::class)->resolve(
            '316e2f33-d8fe-4d9a-b020-b7068aa9398d',
            'IB2600008930',
            'vn0402010696'
        );

        $this->assertTrue($result['current_contractor_won']);
        $this->assertCount(1, $result['contracts']);
        $this->assertCount(2, $result['all_winners']);
        $this->assertSame('218/HĐNT', $result['contracts'][0]['contractNo']);
        $this->assertSame('vn0402010696', $result['contracts'][0]['contractorPassListParsed'][0]['contractorCode']);
        $this->assertSame('CÔNG TY CỔ PHẦN DƯỢC PHẨM NAM SƠN - NAMPHACO', $result['all_winners'][0]['contractorName']);
        $this->assertSame([], $result['verified_lots']);
    }
}
