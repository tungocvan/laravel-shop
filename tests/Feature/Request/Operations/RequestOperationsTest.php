<?php

namespace Tests\Feature\Request\Operations;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\RequestOperationsQuery;
use Modules\Request\Application\Services\RetryRequestOperation;
use Modules\Request\Database\Seeders\RequestStarterTemplateSeeder;
use Modules\Request\Domain\Enums\ExportStatus;
use Modules\Request\Jobs\GenerateRequestExport;
use Modules\Request\Models\RequestExportJob;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Tests\TestCase;

class RequestOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }

    public function test_operations_query_is_bounded(): void
    {
        RequestExportJob::factory()->count(3)->create(['status' => ExportStatus::Failed]);
        config(['request.operations.max_page_size' => 1]);

        $this->assertCount(1, app(RequestOperationsQuery::class)->failures(100));
    }

    public function test_retry_rejects_non_allowlisted_operation(): void
    {
        $this->expectException(ValidationException::class);

        app(RetryRequestOperation::class)->handle('artisan_command', '01ARZ3NDEKTSV4RRFFQ69G5FAV', 1);
    }

    public function test_failed_export_retry_is_idempotent_and_queued_once(): void
    {
        Queue::fake();
        $export = RequestExportJob::factory()->create([
            'status' => ExportStatus::Failed,
            'last_error_code' => 'REQUEST_EXPORT_GENERATION_FAILED',
        ]);

        $service = app(RetryRequestOperation::class);
        $service->handle('export_generation', $export->public_id, 1);
        $service->handle('export_generation', $export->public_id, 1);

        $this->assertSame(ExportStatus::Pending, $export->refresh()->status);
        Queue::assertPushed(GenerateRequestExport::class, 1);
    }

    public function test_starter_template_is_opt_in_and_creates_draft_only(): void
    {
        $actorId = (int) DB::table('users')->insertGetId([
            'name' => 'Starter Admin',
            'email' => 'starter-admin@example.test',
            'is_active' => true,
            'password' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        config([
            'request.settings.starter_templates_enabled' => false,
            'request.settings.starter_template_actor_id' => $actorId,
        ]);
        app(RequestStarterTemplateSeeder::class)->run();
        $this->assertDatabaseMissing('request_groups', ['code' => 'STARTER']);

        config(['request.settings.starter_templates_enabled' => true]);
        app(RequestStarterTemplateSeeder::class)->run();
        app(RequestStarterTemplateSeeder::class)->run();

        $this->assertSame(1, RequestGroup::query()->where('code', 'STARTER')->count());
        $this->assertSame(1, RequestType::query()->where('code', 'GENERAL_APPROVAL')->count());
        $type = RequestType::query()->where('code', 'GENERAL_APPROVAL')->firstOrFail();
        $this->assertNotNull($type->active_draft_version_id);
        $this->assertNull($type->current_published_version_id);
        $this->assertDatabaseHas('request_type_versions', ['id' => $type->active_draft_version_id, 'status' => 'draft']);
    }
}
