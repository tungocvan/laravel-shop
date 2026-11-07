<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavForeignGmpLookup;
use RuntimeException;
use Tests\TestCase;

class PharmaForeignGmpLookupTest extends TestCase
{
    public function test_maps_gmp_certificate_and_preserves_multiline_scope(): void
    {
        Cache::forget('pharma:foreign-gmp:v1:'.hash('sha256', 'Kern Pharma|1'));
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response([
            'success' => true,
            'result' => ['totalCount' => 1, 'items' => [[
                'id' => 5358, 'idCC' => 5135, 'idCongTy' => 'ES-027',
                'tenCoSo' => 'Kern Pharma S.L.',
                'diaChiCoSo' => 'Terrassa (Barcelona)',
                'phamViChungNhan' => "* Thuốc vô trùng:\n+ Dung dịch thể tích nhỏ",
                'nguyenTacGMP' => 'EU-GMP',
                'giayChungNhan' => 'NCF/2630/001/CAT',
                'coQuanCap' => 'Generalitat de Catalunya Departament de Salut',
                'dotCongBo' => '54',
                'ngayCap' => '2026-06-26T00:00:00+07:00',
                'ngayHetHan' => '2027-12-31T00:00:00+07:00',
                'isActive' => true,
            ]]],
        ])]);

        $lookup = app(DavForeignGmpLookup::class);
        $result = $lookup->search('Kern Pharma');

        $this->assertSame(1, $result['total']);
        $item = $result['items'][0];
        $this->assertSame('5358', $item['source_id']);
        $this->assertSame('5135', $item['certificate_record_id']);
        $this->assertSame('ES-027', $item['company_code']);
        $this->assertSame('EU-GMP', $item['standard']);
        $this->assertSame('NCF/2630/001/CAT', $item['certificate']);
        $this->assertSame("* Thuốc vô trùng:\n+ Dung dịch thể tích nhỏ", $item['scope']);
        $this->assertSame('2027-12-31T00:00:00+07:00', $item['expires_at']);
        $this->assertTrue($item['active']);
        $this->assertSame($item, $lookup->findOnPage('Kern Pharma', 1, '5358'));
        $this->assertNull($lookup->findOnPage('Kern Pharma', 1, '5135'));
        Http::assertSentCount(1);
        $recorded = Http::recorded();
        $request = $recorded->first()[0];

        $this->assertSame('POST', $request->method());
        $this->assertSame(
            'https://dichvucong.dav.gov.vn/api/services/app/danhSachCoSoDuocChungNhanGMPNuocNgoai/getAllPublicServerPaging',
            $request->url()
        );

        // Inspect the wire-format JSON: the DAV contract requires an object {},
        // not a JSON array []. PHP array-access can obscure this distinction.
        $payload = json_decode($request->body(), false, 512, JSON_THROW_ON_ERROR);
        $this->assertSame('Kern Pharma', $payload->filter);
        $this->assertSame(0, $payload->skipCount);
        $this->assertSame(15, $payload->maxResultCount);
        $this->assertNull($payload->sorting);
        $this->assertInstanceOf(\\stdClass::class, $payload->thongTinCoSoGMPNuocNgoai);
        $this->assertSame([], get_object_vars($payload->thongTinCoSoGMPNuocNgoai));
        $this->assertFalse($request->hasHeader('Cookie'));
        $this->assertFalse($request->hasHeader('X-XSRF-TOKEN'));
    }

    public function test_empty_public_response_is_not_an_error_and_second_page_uses_offset(): void
    {
        Cache::forget('pharma:foreign-gmp:v1:'.hash('sha256', '|2'));
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response([
            'success' => true, 'result' => ['totalCount' => 0, 'items' => []],
        ])]);

        $this->assertSame(['total' => 0, 'items' => []], app(DavForeignGmpLookup::class)->search('', 2));
        Http::assertSent(fn ($request) => $request['filter'] === ''
            && $request['skipCount'] === 15 && $request['maxResultCount'] === 15);
    }

    public function test_rejects_invalid_source_response(): void
    {
        Cache::forget('pharma:foreign-gmp:v1:'.hash('sha256', 'invalid-gmp|1'));
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response(['success' => false])]);

        $this->expectException(RuntimeException::class);
        app(DavForeignGmpLookup::class)->search('invalid-gmp');
    }

    public function test_routes_are_guarded_and_manifest_exposes_managed_presentation(): void
    {
        $index = Route::getRoutes()->getByName('client.pharma.gmp');
        $detail = Route::getRoutes()->getByName('client.pharma.gmp.show');
        $this->assertNotNull($index);
        $this->assertNotNull($detail);
        $this->assertSame('apps/pharma/gmp', $index->uri());
        $this->assertSame('apps/pharma/gmp/{sourceId}', $detail->uri());
        $this->assertContains('auth:web', $index->gatherMiddleware());
        $this->assertContains('client.feature:pharma,gmp', $index->gatherMiddleware());
        $this->assertContains('client.feature:pharma,gmp', $detail->gatherMiddleware());

        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $feature = $manifest['features']['gmp'];
        $this->assertSame('client.pharma.dav-prices.view', $feature['permission']);
        $this->assertSame('client.pharma.gmp', $feature['route']);
        foreach (['eyebrow', 'page_title', 'page_description'] as $key) {
            $this->assertNotEmpty($feature[$key]);
        }
    }

    public function test_gmp_is_read_only_and_does_not_depend_on_muasamcong(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/DavForeignGmpLookup.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaGmpLookupController.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/gmp-detail.blade.php'));

        $this->assertStringNotContainsString('Modules\\Muasamcong', $service.$controller);
        $this->assertStringNotContainsString('Cookie:', $service);
        $this->assertStringNotContainsString('withoutVerifying', $service);
        $this->assertStringNotContainsString('Medicine::', $controller);
        $this->assertStringContainsString('whitespace-pre-wrap', $detail);
        $this->assertStringContainsString('client.pharma.gmp', $detail);
    }
}
