<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\DavDrugRegistrationLookup;
use RuntimeException;
use Tests\TestCase;

class PharmaDrugRegistrationLookupTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        Cache::flush();
    }

    public function test_maps_nested_dav_response_and_preserves_distinct_registration_numbers(): void
    {
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response([
            'success' => true,
            'result' => [
                'totalCount' => 3,
                'items' => [
                    $this->row(65564, '539110203326', '10mg'),
                    $this->row(65563, '539110203226', '100mg'),
                    $this->row(65562, '539110203126', '50mg'),
                ],
            ],
        ])]);
        $lookup = app(DavDrugRegistrationLookup::class);
        $result = $lookup->search('name', 'Venclexta');
        $this->assertSame(3, $result['total']);
        $this->assertSame(['539110203326', '539110203226', '539110203126'], array_column($result['items'], 'registration'));
        $this->assertSame(['10mg', '100mg', '50mg'], array_column($result['items'], 'strength'));
        $this->assertSame('Venetoclax', $result['items'][0]['ingredient']);
        $this->assertSame('AbbVie Ireland NL B.V', $result['items'][0]['manufacturer']);
        $this->assertSame('2029-09-18T00:00:00+07:00', $result['items'][0]['expires_at']);
        $this->assertSame('539110203226', $lookup->findOnPage('name', 'Venclexta', 1, '65563')['registration']);
        $this->assertNull($lookup->findOnPage('name', 'Venclexta', 1, '9999'));

        Http::assertSent(fn ($request) =>
            $request->method() === 'POST'
            && $request->url() === 'https://dichvucong.dav.gov.vn/api/services/app/soDangKy/GetAllPublicServerPaging'
            && $request['SoDangKyThuoc']['TenThuoc'] === 'Venclexta'
            && $request['KichHoat'] === true
            && $request['skipCount'] === 0
            && $request['maxResultCount'] === 10
            && ! $request->hasHeader('Cookie')
            && ! $request->hasHeader('X-XSRF-TOKEN')
        );
    }

    public function test_rejects_malformed_response_and_does_not_use_a_database(): void
    {
        Http::preventStrayRequests();
        Http::fake(['dichvucong.dav.gov.vn/*' => Http::response(['success' => true, 'result' => ['items' => null]], 200)]);
        $this->expectException(RuntimeException::class);
        app(DavDrugRegistrationLookup::class)->search('name', 'bad');
    }

    public function test_routes_are_read_only_and_feature_protected(): void
    {
        foreach (['client.pharma.drug-registrations', 'client.pharma.drug-registrations.show'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertSame(['GET', 'HEAD'], $route->methods());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.feature:pharma,drug-registrations', $route->gatherMiddleware());
        }
        $manifest = require base_path('Modules/ClientPortal/Applications/Pharma/manifest.php');
        $this->assertArrayHasKey('drug-registrations', $manifest['features']);
        $this->assertArrayNotHasKey('drug-registrations', $manifest['navigation']);
    }

    private function row(int $id, string $registration, string $strength): array
    {
        return [
            'id' => $id,
            'tenThuoc' => 'Venclexta',
            'soDangKy' => $registration,
            'thongTinThuocCoBan' => [
                'hoatChatChinh' => 'Venetoclax',
                'hamLuong' => $strength,
                'dangBaoChe' => 'Viên nén bao phim',
            ],
            'thongTinDangKyThuoc' => [
                'ngayHetHanSoDangKy' => '2029-09-18T00:00:00+07:00',
            ],
            'congTySanXuat' => ['tenCongTySanXuat' => 'AbbVie Ireland NL B.V'],
        ];
    }
}
