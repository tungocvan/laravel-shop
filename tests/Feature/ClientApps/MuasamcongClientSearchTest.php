<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Mockery;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Tests\TestCase;

class MuasamcongClientSearchTest extends TestCase
{
    public function test_client_drug_pricing_routes_enforce_application_and_feature_access(): void
    {
        foreach ([
            'client.muasamcong.drug-pricing' => 'apps/muasamcong/drug-pricing',
            'client.muasamcong.drug-pricing.detail' => 'apps/muasamcong/drug-pricing/{sourceId}',
            'client.muasamcong.drug-pricing.sync' => 'apps/muasamcong/drug-pricing/sync',
        ] as $routeName => $uri) {
            $route = Route::getRoutes()->getByName($routeName);
            $this->assertNotNull($route);
            $this->assertSame($uri, $route->uri());
            $this->assertContains('auth:web', $route->gatherMiddleware());
            $this->assertContains('client.application:muasamcong', $route->gatherMiddleware());
            $this->assertContains('client.feature:muasamcong,drug-pricing', $route->gatherMiddleware());
        }
    }

    public function test_client_search_reuses_domain_service_and_applies_filters_and_price_sort(): void
    {
        $service = Mockery::mock(MuaSamCongService::class);
        $service->shouldReceive('searchPricing')->once()->with('Paracetamol 500mg')->andReturn([
            'success' => true,
            'status' => 200,
            'data' => [
                'total' => 3,
                'items' => [
                    ['id' => '11111111-1111-4111-8111-111111111111', 'tenThuoc' => 'Paracetamol A', 'tenHoatChat' => 'Paracetamol', 'nhomThuoc' => 'Nhóm 1', 'winningName' => ['Công ty A'], 'donGia' => 3000],
                    ['id' => '22222222-2222-4222-8222-222222222222', 'tenThuoc' => 'Paracetamol B', 'tenHoatChat' => 'Paracetamol', 'nhomThuoc' => 'Nhóm 1', 'winningName' => ['Công ty A'], 'donGia' => 1000],
                    ['id' => '33333333-3333-4333-8333-333333333333', 'tenThuoc' => 'Thuốc khác', 'tenHoatChat' => 'Ibuprofen', 'nhomThuoc' => 'Nhóm 2', 'winningName' => ['Công ty B'], 'donGia' => 2000],
                ],
            ],
        ]);

        $snapshots = Mockery::mock(PricingSearchSnapshotService::class);
        $snapshots->shouldReceive('find')->once()->with('Paracetamol 500mg')->andReturnNull();
        $snapshots->shouldReceive('store')->once();
        $syncService = Mockery::mock(PricingResultSyncService::class);
        $syncService->shouldReceive('existingSourceIds')->once()->andReturn([]);
        $registry = Mockery::mock(ApplicationRegistry::class);

        $request = Request::create('/apps/muasamcong/drug-pricing', 'GET', [
            'keyword' => 'Paracetamol 500mg',
            'active_ingredient' => 'Paracetamol',
            'winning_company' => 'Công ty A',
            'sort_price' => 'asc',
        ]);

        $view = (new MuasamcongApplicationController())->drugPricing($request, $service, $snapshots, $syncService, $registry);
        $data = $view->getData();

        $this->assertSame('Paracetamol 500mg', $data['keyword']);
        $this->assertCount(2, $data['items']);
        $this->assertSame(2, $data['summary']['total']);
        $this->assertSame(1000.0, $data['summary']['lowest_price']);
        $this->assertSame(2000.0, $data['summary']['average_price']);
        $this->assertSame(3000.0, $data['summary']['highest_price']);
        $this->assertSame(1000, $data['items']->first()['donGia']);
    }

    public function test_client_manifest_exposes_separate_queued_sync_permission(): void
    {
        $definitions = app(ApplicationPermissionService::class)->definitions()->pluck('name');
        $this->assertTrue($definitions->contains('client.muasamcong.drug-pricing.view'));
        $this->assertTrue($definitions->contains('client.muasamcong.drug-pricing.sync'));
    }
}
