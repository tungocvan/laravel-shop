<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Pharma\Services\PublicAwardPriceLookup;
use Tests\TestCase;

class PharmaAwardPriceLookupTest extends TestCase
{
    public function test_public_award_lookup_uses_public_endpoint_without_muasamcong_services(): void
    {
        Cache::flush();
        Http::fake([
            'muasamcong.mof.gov.vn/*' => Http::response([
                'page' => [
                    'content' => [['id' => 'award-1', 'tenThuoc' => 'Cold Wind', 'donViTinh' => 'Ống', 'donGia' => 5900]],
                    'totalElements' => 1,
                ],
            ]),
        ]);

        $result = app(PublicAwardPriceLookup::class)->search('Cold Wind');

        $this->assertTrue($result['success']);
        $this->assertSame(1, $result['total']);
        $this->assertSame('Ống', $result['items'][0]['donViTinh']);
        Http::assertSent(fn ($request) =>
            str_contains($request->url(), '/services/smart/search_prc')
            && $request->method() === 'POST'
            && $request->data()[0]['query'][0]['keyWord'] === 'Cold Wind');
    }

    public function test_invalid_remote_response_is_reported_without_disclosing_payload(): void
    {
        Cache::flush();
        Http::fake(['muasamcong.mof.gov.vn/*' => Http::response(['unexpected' => true])]);

        $result = app(PublicAwardPriceLookup::class)->search('not found response');
        $this->assertFalse($result['success']);
        $this->assertSame([], $result['items']);
    }
}
