<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Mockery;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;
use Modules\ClientPortal\Applications\Muasamcong\Jobs\SyncPricingResultsJob;
use Modules\ClientPortal\Models\SyncRequest;
use Modules\Muasamcong\Services\MuaSamCongService;
use Modules\Muasamcong\Services\PricingResultSyncService;
use Modules\Muasamcong\Services\PricingTbmtPaginationService;
use RuntimeException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class MuasamcongQueueStatusTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Schema::hasTable('client_portal_sync_requests')) {
            Schema::create('client_portal_sync_requests', function (Blueprint $table): void {
                $table->uuid('id')->primary();
                $table->unsignedBigInteger('user_id')->nullable()->index();
                $table->string('application_key', 100);
                $table->string('feature_key', 100);
                $table->string('keyword', 200)->nullable();
                $table->json('source_ids')->nullable();
                $table->unsignedInteger('selected_count')->default(0);
                $table->string('status', 30)->default('queued');
                $table->unsignedInteger('inserted_count')->default(0);
                $table->unsignedInteger('duplicate_count')->default(0);
                $table->unsignedInteger('missing_count')->default(0);
                $table->text('error_message')->nullable();
                $table->timestamp('started_at')->nullable();
                $table->timestamp('finished_at')->nullable();
                $table->timestamps();
            });
        }
    }

    public function test_sync_status_route_is_protected_by_client_application_and_feature_middleware(): void
    {
        $route = Route::getRoutes()->getByName('client.muasamcong.drug-pricing.sync-status');

        $this->assertNotNull($route);
        $this->assertSame('apps/muasamcong/drug-pricing/sync/{syncRequest}/status', $route->uri());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.application:muasamcong', $route->gatherMiddleware());
        $this->assertContains('client.feature:muasamcong,drug-pricing', $route->gatherMiddleware());
    }

    public function test_sync_job_moves_request_from_queued_to_completed_with_summary(): void
    {
        $id = '11111111-1111-4111-8111-111111111111';
        SyncRequest::query()->create([
            'id' => $id,
            'user_id' => 501,
            'application_key' => 'muasamcong',
            'feature_key' => 'drug-pricing',
            'keyword' => 'Gourcuff',
            'source_ids' => ['22222222-2222-4222-8222-222222222222'],
            'selected_count' => 1,
            'status' => 'queued',
        ]);

        $source = Mockery::mock(MuaSamCongService::class);
        $source->shouldReceive('searchPricing')->once()->with('Gourcuff')->andReturn([
            'success' => true,
            'data' => ['items' => [['id' => '22222222-2222-4222-8222-222222222222']]],
        ]);
        $pagination = Mockery::mock(PricingTbmtPaginationService::class);
        $pagination->shouldReceive('isTbmtKeyword')->once()->with('Gourcuff')->andReturnFalse();
        $sync = Mockery::mock(PricingResultSyncService::class);
        $sync->shouldReceive('syncSelected')->once()->andReturn([
            'inserted' => 1,
            'duplicates' => 0,
            'missing' => 0,
            'selected' => 1,
        ]);

        (new SyncPricingResultsJob('Gourcuff', ['22222222-2222-4222-8222-222222222222'], 501, $id))
            ->handle($source, $pagination, $sync);

        $request = SyncRequest::query()->findOrFail($id);
        $this->assertSame('completed', $request->status);
        $this->assertSame(1, $request->inserted_count);
        $this->assertNotNull($request->started_at);
        $this->assertNotNull($request->finished_at);
    }

    public function test_failed_job_marks_sync_request_failed(): void
    {
        $id = '33333333-3333-4333-8333-333333333333';
        SyncRequest::query()->create([
            'id' => $id,
            'user_id' => 501,
            'application_key' => 'muasamcong',
            'feature_key' => 'drug-pricing',
            'selected_count' => 1,
            'status' => 'processing',
        ]);

        (new SyncPricingResultsJob('Gourcuff', [], 501, $id))->failed(new RuntimeException('API unavailable'));

        $request = SyncRequest::query()->findOrFail($id);
        $this->assertSame('failed', $request->status);
        $this->assertSame('API unavailable', $request->error_message);
        $this->assertNotNull($request->finished_at);
    }

    public function test_status_endpoint_returns_only_request_owned_by_authenticated_client(): void
    {
        $id = '44444444-4444-4444-8444-444444444444';
        SyncRequest::query()->create([
            'id' => $id,
            'user_id' => 501,
            'application_key' => 'muasamcong',
            'feature_key' => 'drug-pricing',
            'selected_count' => 2,
            'inserted_count' => 1,
            'duplicate_count' => 1,
            'status' => 'completed',
            'finished_at' => now(),
        ]);

        $user = new User();
        $user->id = 501;
        $request = Request::create('/status', 'GET');
        $request->setUserResolver(fn (?string $guard = null) => $user);

        $response = (new MuasamcongApplicationController())->drugPricingSyncStatus($request, $id);
        $payload = $response->getData(true);

        $this->assertSame('completed', $payload['status']);
        $this->assertSame(2, $payload['selected']);
        $this->assertSame(1, $payload['inserted']);
        $this->assertSame(1, $payload['duplicates']);
    }
}
