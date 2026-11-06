<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Modules\Pharma\Services\DavPublicPriceLookup;
use Tests\TestCase;

class PharmaDavPublicPriceLookupTest extends TestCase
{
    public function test_maps_public_prices_and_preserves_missing_price(): void
    {
        Cache::forget('pharma:dav:'.hash('sha256', 'METHYL 4 LEMED|1'));
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response([
            'success' => true,
            'result' => ['totalCount' => 1, 'items' => [[
                'tenThuoc' => 'METHYL 4 LEMED',
                'soDangKy' => '893110605624',
                'giaBanBuon' => 1200,
                'giaBanLe' => null,
                'id' => 107446,
                'dangBaoChe' => 'Viên nén',
                'nuocSanXuat' => 'Việt Nam',
                'trangThaiCongBo' => '<span>Đã công bố</span>',
            ]]],
        ])]);

        $result = app(DavPublicPriceLookup::class)->search('METHYL 4 LEMED');

        $this->assertSame(1, $result['total']);
        $this->assertSame('893110605624', $result['items'][0]['registration']);
        $this->assertSame(1200.0, $result['items'][0]['wholesale']);
        $this->assertNull($result['items'][0]['retail']);
        $this->assertSame('Đã công bố', $result['items'][0]['status']);
        $this->assertSame('Viên nén', $result['items'][0]['dosage']);
        $this->assertSame('Việt Nam', $result['items'][0]['country']);
        $this->assertSame('107446', $result['items'][0]['source_id']);
        Http::assertSent(fn ($request) => $request['filterAll'] === 'METHYL 4 LEMED'
            && $request['skipCount'] === 0 && $request['maxResultCount'] === 15);
    }

    public function test_rejects_invalid_source_payload(): void
    {
        Cache::forget('pharma:dav:'.hash('sha256', 'invalid-payload|1'));
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response(['success' => false])]);

        $this->expectException(\RuntimeException::class);
        app(DavPublicPriceLookup::class)->search('invalid-payload');
    }

    public function test_feature_declares_managed_presentation_and_permission(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $feature = $manifest['features']['dav-prices'];

        $this->assertSame('client.pharma.dav-prices.view', $feature['permission']);
        $this->assertSame('client.pharma.dav-prices', $feature['route']);
        foreach (['eyebrow', 'page_title', 'page_description'] as $key) {
            $this->assertNotEmpty($feature[$key]);
        }
    }
}
