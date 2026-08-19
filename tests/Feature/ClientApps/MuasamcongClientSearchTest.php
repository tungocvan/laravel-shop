<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;
use Modules\ClientPortal\Applications\Muasamcong\Services\ClientPricingSearchService;
use Modules\ClientPortal\Services\ApplicationPermissionService;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\Muasamcong\Models\PricingResult;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingSearchSnapshotService;
use Tests\TestCase;

class MuasamcongClientSearchTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('muasamcong_pricing_results')) {
            Schema::create('muasamcong_pricing_results', function (Blueprint $table): void {
                $table->id();
                $table->uuid('source_id')->unique();
                $table->string('ten_thuoc')->nullable();
                $table->string('ten_hoat_chat')->nullable();
                $table->decimal('don_gia', 18, 4)->nullable();
                $table->json('winning_name')->nullable();
                $table->json('raw_payload')->nullable();
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();
            });
        }
    }

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

    public function test_client_search_uses_orchestrator_and_applies_filters_and_price_sort(): void
    {
        $search = Mockery::mock(ClientPricingSearchService::class);
        $search->shouldReceive('search')->once()->with('Paracetamol 500mg', null, false)->andReturn([
            'source' => 'api',
            'result' => [
                'success' => true,
                'data' => [
                    'total' => 3,
                    'items' => [
                        ['id' => '11111111-1111-4111-8111-111111111111', 'tenThuoc' => 'Paracetamol A', 'tenHoatChat' => 'Paracetamol', 'nhomThuoc' => 'Nhóm 1', 'winningName' => ['Công ty A'], 'donGia' => 3000],
                        ['id' => '22222222-2222-4222-8222-222222222222', 'tenThuoc' => 'Paracetamol B', 'tenHoatChat' => 'Paracetamol', 'nhomThuoc' => 'Nhóm 1', 'winningName' => ['Công ty A'], 'donGia' => 1000],
                        ['id' => '33333333-3333-4333-8333-333333333333', 'tenThuoc' => 'Thuốc khác', 'tenHoatChat' => 'Ibuprofen', 'nhomThuoc' => 'Nhóm 2', 'winningName' => ['Công ty B'], 'donGia' => 2000],
                    ],
                ],
            ],
        ]);
        $syncService = Mockery::mock(PricingResultSyncService::class);
        $syncService->shouldReceive('existingSourceIds')->once()->andReturn([]);
        $registry = Mockery::mock(ApplicationRegistry::class);

        $request = Request::create('/apps/muasamcong/drug-pricing', 'GET', [
            'keyword' => 'Paracetamol 500mg',
            'active_ingredient' => 'Paracetamol',
            'winning_company' => 'Công ty A',
            'sort_price' => 'asc',
        ]);

        $view = (new MuasamcongApplicationController())->drugPricing($request, $search, $syncService, $registry);
        $data = $view->getData();

        $this->assertSame('Paracetamol 500mg', $data['keyword']);
        $this->assertSame('api', $data['dataSource']);
        $this->assertCount(2, $data['items']);
        $this->assertSame(2, $data['summary']['total']);
        $this->assertSame(1000.0, $data['summary']['lowest_price']);
        $this->assertSame(2000.0, $data['summary']['average_price']);
        $this->assertSame(3000.0, $data['summary']['highest_price']);
        $this->assertSame(1000, $data['items']->first()['donGia']);
    }

    public function test_database_first_search_does_not_call_api_when_synced_data_exists(): void
    {
        PricingResult::query()->create([
            'source_id' => '44444444-4444-4444-8444-444444444444',
            'ten_thuoc' => 'Gourcuff-2,5',
            'ten_hoat_chat' => 'Alfuzosin hydrochlorid',
            'don_gia' => 3420,
            'winning_name' => ['Công ty A'],
            'synced_at' => now(),
        ]);

        $api = Mockery::mock(MuaSamCongService::class);
        $api->shouldNotReceive('searchPricing');
        $snapshots = Mockery::mock(PricingSearchSnapshotService::class);
        $snapshots->shouldNotReceive('find');

        $search = new ClientPricingSearchService($api, $snapshots);
        $response = $search->search('Gourcuff');

        $this->assertSame('synced', $response['source']);
        $this->assertTrue($response['result']['success']);
        $this->assertSame('Gourcuff-2,5', $response['result']['data']['items'][0]['tenThuoc']);
    }

    public function test_database_first_search_uses_snapshot_before_api(): void
    {
        $api = Mockery::mock(MuaSamCongService::class);
        $api->shouldNotReceive('searchPricing');
        $snapshot = new \Modules\Muasamcong\Models\PricingSearchSnapshot();
        $snapshot->result_payload = ['success' => true, 'data' => ['total' => 1, 'items' => [['id' => '55555555-5555-4555-8555-555555555555', 'tenThuoc' => 'Snapshot drug']]]];
        $snapshots = Mockery::mock(PricingSearchSnapshotService::class);
        $snapshots->shouldReceive('find')->once()->with('Snapshot keyword')->andReturn($snapshot);

        $response = (new ClientPricingSearchService($api, $snapshots))->search('Snapshot keyword');

        $this->assertSame('snapshot', $response['source']);
        $this->assertSame('Snapshot drug', $response['result']['data']['items'][0]['tenThuoc']);
    }

    public function test_force_refresh_bypasses_local_database_and_snapshot(): void
    {
        $api = Mockery::mock(MuaSamCongService::class);
        $api->shouldReceive('searchPricing')->once()->with('Gourcuff')->andReturn(['success' => true, 'data' => ['total' => 0, 'items' => []]]);
        $snapshots = Mockery::mock(PricingSearchSnapshotService::class);
        $snapshots->shouldNotReceive('find');
        $snapshots->shouldReceive('store')->once();

        $response = (new ClientPricingSearchService($api, $snapshots))->search('Gourcuff', null, true);

        $this->assertSame('api', $response['source']);
    }

    public function test_client_manifest_exposes_separate_queued_sync_permission(): void
    {
        $definitions = app(ApplicationPermissionService::class)->definitions()->pluck('name');
        $this->assertTrue($definitions->contains('client.muasamcong.drug-pricing.view'));
        $this->assertTrue($definitions->contains('client.muasamcong.drug-pricing.sync'));
    }
}
