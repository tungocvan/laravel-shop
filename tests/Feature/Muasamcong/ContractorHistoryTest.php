<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Tests\TestCase;

class ContractorHistoryTest extends TestCase
{
    public function test_contractor_history_uses_org_code_and_collects_all_pages(): void
    {
        config()->set('muasamcong.session_cookie', 'JSESSIONID=test');
        config()->set('muasamcong.verify_ssl', true);
        config()->set('muasamcong.endpoints.contractor_joined_bids', 'https://muasamcong.mpi.gov.vn/o/egp-portal-personal-page/services/get-list-notify-contractor-join');
        config()->set('muasamcong.referers.contractor_joined_bids', 'https://muasamcong.mpi.gov.vn/web/guest/profile-info?menu=tender-pakage-list');

        Http::fake(function ($request) {
            $payload = $request->data();
            $page = $payload['pageNumber'];

            $this->assertSame('vn0314492345', $payload['request']['orgCode']);
            $this->assertSame('2021-01-01T00:00:00.000Z', $payload['request']['fromDate']);

            return Http::response([
                'listBid' => [
                    'content' => [[
                        'id' => $page.'-id',
                        'notifyNo' => $page === 1 ? 'IB260001' : 'IB250001',
                        'bidName' => 'Gói '.$page,
                        'contractorCode' => 'vn0314492345',
                        'createdDate' => $page === 1 ? '2026-01-01T00:00:00' : '2025-01-01T00:00:00',
                        'dateYear' => $page === 1 ? '2026' : '2025',
                    ]],
                    'totalPages' => 2,
                    'totalElements' => 2,
                ],
            ]);
        });

        $result = app(ContractorHistoryService::class)->search('vn0314492345');

        $this->assertSame(2, $result['total']);
        $this->assertSame(2, $result['reported_total']);
        $this->assertSame(['IB260001', 'IB250001'], array_column($result['items'], 'notifyNo'));
        Http::assertSentCount(2);
    }

    public function test_contractor_history_rejects_unapproved_endpoint(): void
    {
        config()->set('muasamcong.session_cookie', 'JSESSIONID=test');
        config()->set('muasamcong.endpoints.contractor_joined_bids', 'https://example.com/history');

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Endpoint lịch sử nhà thầu không được phép.');

        app(ContractorHistoryService::class)->search('vn0314492345');
    }
}
