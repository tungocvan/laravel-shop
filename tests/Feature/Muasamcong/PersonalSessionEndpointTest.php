<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Http;
use Modules\Muasamcong\Services\ContractorHistoryService;
use Modules\Muasamcong\Services\PersonalSessionService;
use Tests\TestCase;

class PersonalSessionEndpointTest extends TestCase
{
    public function test_session_check_accepts_mof_endpoint_and_uses_post(): void
    {
        config([
            'muasamcong.endpoints.contractor_joined_bids' => 'https://muasamcong.mof.gov.vn/o/egp-portal-personal-page/services/get-list-notify-contractor-join',
        ]);

        Http::fake(['*' => Http::response(['listBid' => ['content' => [], 'totalElements' => 0, 'totalPages' => 0]])]);

        $sessions = \Mockery::mock(PersonalSessionService::class);
        $sessions->shouldReceive('cookie')->andReturn('test-cookie');

        $result = (new ContractorHistoryService($sessions))->testSession();

        $this->assertSame(0, $result['total']);
        Http::assertSent(fn ($request) => $request->method() === 'POST'
            && str_starts_with($request->url(), 'https://muasamcong.mof.gov.vn/o/egp-portal-personal-page/services/get-list-notify-contractor-join'));
    }

    public function test_session_check_rejects_untrusted_endpoint(): void
    {
        config(['muasamcong.endpoints.contractor_joined_bids' => 'https://example.com/steal']);

        $sessions = \Mockery::mock(PersonalSessionService::class);
        $sessions->shouldReceive('cookie')->andReturn('test-cookie');

        $this->expectException(\RuntimeException::class);
        (new ContractorHistoryService($sessions))->testSession();
    }
}
