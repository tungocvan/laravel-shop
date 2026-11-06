<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\MohTraditionalPriceLookup;
use Tests\TestCase;

class PharmaTraditionalPriceLookupTest extends TestCase
{
    public function test_moh_source_maps_ingredients_package_and_wholesale_price(): void
    {
        Cache::flush();
        Http::fake(['ydct-dichvucong.moh.gov.vn/*' => Http::response([
            'success' => true,
            'result' => ['totalCount' => 2, 'items' => [
                ['id' => 463, 'tenThuoc' => 'Cold Wind', 'soDangKy' => 'TCT-00158-23',
                    'thanhPhan' => 'Sài hồ 600 mg', 'quyCachDongGoi' => 'Hộp 03 vỉ x 05 ống',
                    'donViTinh' => 'Ống', 'giaBanBuon' => 5900, 'dangBaoChe' => 'Siro thuốc',
                    'nuocSanXuat' => 'Việt Nam', 'trangThaiCongBo' => '<span>Đã công bố</span>'],
                ['id' => 462, 'tenThuoc' => 'Cold Wind', 'soDangKy' => 'TCT-00158-23',
                    'thanhPhan' => 'Sài hồ 600 mg', 'quyCachDongGoi' => 'Hộp 01 lọ x 100 ml',
                    'donViTinh' => 'Lọ', 'giaBanBuon' => 59000],
            ]],
        ], 200)]);

        $result = app(MohTraditionalPriceLookup::class)->search('cold wind');
        $this->assertSame(2, $result['total']);
        $this->assertSame('463', $result['items'][0]['source_id']);
        $this->assertSame('Sài hồ 600 mg', $result['items'][0]['ingredient']);
        $this->assertSame('Siro thuốc', $result['items'][0]['dosage']);
        $this->assertSame('Việt Nam', $result['items'][0]['country']);
        $this->assertSame(5900.0, $result['items'][0]['wholesale']);
        $this->assertSame(59000.0, $result['items'][1]['wholesale']);
        $this->assertSame('Đã công bố', $result['items'][0]['status']);

        Http::assertSent(fn ($request) => $request->method() === 'POST' &&
            $request['filterAll'] === 'cold wind' &&
            $request['skipCount'] === 0 &&
            $request['maxResultCount'] === 15 &&
            ! $request->hasHeader('Cookie') &&
            ! $request->hasHeader('X-XSRF-TOKEN'));
    }

    public function test_traditional_lookup_routes_are_feature_guarded(): void
    {
        foreach ([
            'client.pharma.traditional-prices' => 'GET',
            'client.pharma.traditional-prices.import.preview' => 'GET',
            'client.pharma.traditional-prices.import.store' => 'POST',
        ] as $name => $method) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains($method, $route->methods());
            $this->assertContains('client.feature:pharma,traditional-prices', $route->gatherMiddleware());
        }
    }

    public function test_traditional_import_is_typed_and_does_not_set_planned_price(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaTraditionalPriceController.php'));
        $this->assertStringContainsString('Medicine::PRODUCT_TYPE_TRADITIONAL', $source);
        $this->assertStringContainsString("'declared_price' => \$candidate['wholesale']", $source);
        $this->assertStringNotContainsString("'selling_price' =>", $source);
        $this->assertStringContainsString('hasMultiplePackages', $source);
    }
}
