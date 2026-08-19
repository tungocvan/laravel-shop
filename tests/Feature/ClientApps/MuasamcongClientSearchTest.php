<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\Muasamcong\Http\Controllers\Client\MuasamcongClientController;
use Modules\Muasamcong\Services\MuaSamCongService;
use Tests\TestCase;

class MuasamcongClientSearchTest extends TestCase
{
    public function test_client_drug_pricing_route_enforces_application_and_feature_access(): void
    {
        $route = Route::getRoutes()->getByName('client.muasamcong.drug-pricing');

        $this->assertNotNull($route);
        $this->assertSame('apps/muasamcong/drug-pricing', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:muasamcong', $route->gatherMiddleware());
        $this->assertContains('client.feature:muasamcong,drug-pricing', $route->gatherMiddleware());
    }

    public function test_client_search_reuses_muasamcong_service_and_builds_price_summary(): void
    {
        $service = Mockery::mock(MuaSamCongService::class);
        $service->shouldReceive('searchPricing')
            ->once()
            ->with('Paracetamol 500mg')
            ->andReturn([
                'success' => true,
                'status' => 200,
                'data' => [
                    'total' => 3,
                    'items' => [
                        ['tenThuoc' => 'Paracetamol', 'donGia' => 1000],
                        ['tenThuoc' => 'Paracetamol', 'donGia' => 2000],
                        ['tenThuoc' => 'Paracetamol', 'donGia' => 3000],
                    ],
                ],
            ]);

        $request = Request::create('/apps/muasamcong/drug-pricing', 'GET', [
            'keyword' => 'Paracetamol 500mg',
        ]);

        $view = (new MuasamcongClientController())->drugPricing($request, $service);
        $data = $view->getData();

        $this->assertSame('Paracetamol 500mg', $data['keyword']);
        $this->assertCount(3, $data['items']);
        $this->assertSame(3, $data['summary']['total']);
        $this->assertSame(1000.0, $data['summary']['lowest_price']);
        $this->assertSame(2000.0, $data['summary']['average_price']);
        $this->assertSame(3000.0, $data['summary']['highest_price']);
    }
}
