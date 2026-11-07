<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavDrugRegistrationLookup;
use Modules\Pharma\Services\TraditionalDrugRegistrationLookup;
use RuntimeException;
use Tests\TestCase;

class PharmaTraditionalDrugRegistrationLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_cold_wind_is_mapped_without_inventing_expiry_and_with_multiline_packaging(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'ydct-dichvucong.moh.gov.vn/*' => Http::response([
                'success' => true,
                'result' => [
                    'totalCount' => 1,
                    'items' => [[
                        'id' => 3886,
                        'tenThuoc' => 'Cold Wind',
                        'soDangKy' => 'TCT-00158-23',
                        'isActive' => true,
                        'thongTinThuocCoBan' => [
                            'hoatChatChinh' => 'Mỗi 10ml siro chứa dịch chiết xuất từ các dược liệu: Sài hồ 600mg; Cam thảo 300mg.',
                            'hamLuong' => '600mg; 600mg; 300mg',
                            'dangBaoChe' => 'Siro thuốc',
                            'dongGoi' => "Hộp 03 vỉ x 05 ống x 10ml.\nHộp 01 lọ x 100ml.",
                            'hanDung' => '36 tháng',
                        ],
                        'thongTinDangKyThuoc' => [
                            'ngayCapSoDangKy' => '2023-12-28T00:00:00+07:00',
                            'ngayHetHanSoDangKy' => null,
                            'soQuyetDinh' => '446/QĐ-YDCT',
                            'dotCap' => 'Cấp bổ sung',
                        ],
                        'congTySanXuat' => [
                            'tenCongTySanXuat' => 'Công ty cổ phần dược quốc tế Tùng Lộc',
                            'nuocSanXuat' => 'Việt Nam',
                        ],
                        'congTyDangKy' => [
                            'tenCongTyDangKy' => 'Công ty cổ phần thương mại Q&V Việt Nam',
                        ],
                    ]],
                ],
            ]),
        ]);

        $service = app(TraditionalDrugRegistrationLookup::class);
        $this->assertSame(15, $service->pageSize());
        $result = $service->search('name', 'cold wind');
        $this->assertSame(1, $result['total']);
        $item = $result['items'][0];
        $this->assertSame('3886', $item['source_id']);
        $this->assertSame('TCT-00158-23', $item['registration']);
        $this->assertSame('Siro thuốc', $item['dosage_form']);
        $this->assertSame('36 tháng', $item['shelf_life']);
        $this->assertSame('', $item['expires_at']);
        $this->assertSame('446/QĐ-YDCT', $item['decision']);
        $this->assertSame('Cấp bổ sung', $item['batch']);
        $this->assertStringContainsString("\n", $item['packaging']);
        $this->assertStringContainsString('Sài hồ', $item['ingredient']);
        $this->assertSame('Công ty cổ phần dược quốc tế Tùng Lộc', $item['manufacturer']);
        $this->assertSame('TCT-00158-23', $service->findOnPage('name', 'cold wind', 1, '3886')['registration']);
        $this->assertNull($service->findOnPage('name', 'cold wind', 1, '9999'));

        Http::assertSent(fn ($request) =>
            $request->method() === 'POST'
            && $request->url() === 'https://ydct-dichvucong.moh.gov.vn/api/services/app/soDangKy/GetAllPublicServerPaging'
            && $request['SoDangKyThuoc']['TenThuoc'] === 'cold wind'
            && $request['KichHoat'] === true
            && $request['skipCount'] === 0
            && $request['maxResultCount'] === 15
            && ! $request->hasHeader('Cookie')
            && ! $request->hasHeader('X-XSRF-TOKEN')
        );
    }

    public function test_sources_have_isolated_caches_and_distinct_endpoints(): void
    {
        Http::preventStrayRequests();
        Http::fake([
            'ydct-dichvucong.moh.gov.vn/*' => Http::response(['success' => true, 'result' => ['totalCount' => 0, 'items' => []]]),
            'dichvucong.dav.gov.vn/*' => Http::response(['success' => true, 'result' => ['totalCount' => 0, 'items' => []]]),
        ]);
        app(TraditionalDrugRegistrationLookup::class)->search('name', 'same keyword');
        app(DavDrugRegistrationLookup::class)->search('name', 'same keyword');
        Http::assertSentCount(2);
    }

    public function test_moh_transport_enforces_verified_ecdhe_without_disabling_certificate_validation(): void
    {
        $moh = new \ReflectionMethod(TraditionalDrugRegistrationLookup::class, 'transportOptions');
        $options = $moh->invoke(app(TraditionalDrugRegistrationLookup::class));
        $this->assertSame(
            CURL_SSLVERSION_TLSv1_2 | CURL_SSLVERSION_MAX_TLSv1_2,
            $options['curl'][CURLOPT_SSLVERSION]
        );
        $this->assertSame('ECDHE+AESGCM:ECDHE+CHACHA20', $options['curl'][CURLOPT_SSL_CIPHER_LIST]);
        $this->assertArrayNotHasKey(CURLOPT_SSL_VERIFYPEER, $options['curl']);
        $this->assertArrayNotHasKey(CURLOPT_SSL_VERIFYHOST, $options['curl']);

        $dav = new \ReflectionMethod(DavDrugRegistrationLookup::class, 'transportOptions');
        $this->assertSame([], $dav->invoke(app(DavDrugRegistrationLookup::class)));
    }

    public function test_malformed_moh_response_is_rejected(): void
    {
        Http::preventStrayRequests();
        Http::fake(['ydct-dichvucong.moh.gov.vn/*' => Http::response(['success' => false, 'result' => null])]);
        $this->expectException(RuntimeException::class);
        app(TraditionalDrugRegistrationLookup::class)->search('name', 'invalid');
    }

    public function test_traditional_routes_are_get_only_and_feature_protected(): void
    {
        foreach (['client.pharma.traditional-drug-registrations', 'client.pharma.traditional-drug-registrations.show'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.feature:pharma,traditional-drug-registrations', $route->gatherMiddleware());
        }
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $this->assertArrayHasKey('traditional-drug-registrations', $manifest['features']);
        $this->assertArrayNotHasKey('traditional-drug-registrations', $manifest['navigation']);
        $this->assertSame('client.pharma.dav-prices.view', $manifest['features']['traditional-drug-registrations']['permission']);
    }
}
