<?php

namespace Tests\Feature\Request\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\PlanRequestExport;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Support\RequestPrivateExportStorage;
use RuntimeException;
use Tests\TestCase;

class RequestExportFoundationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }

    public function test_export_query_is_scoped_to_current_authorization(): void
    {
        InternalRequest::factory()->count(2)->create(['requester_id' => 41]);
        InternalRequest::factory()->count(3)->create(['requester_id' => 99]);

        $user = $this->user(41, ['request.export', 'request.instance.view-own']);
        $query = app(RequestExportQuery::class);

        $this->assertSame(2, $query->countBounded($user, [], 100));
        $this->assertSame([41, 41], $query->queryFor($user)->pluck('requester_id')->all());
    }

    public function test_export_planner_switches_to_queue_and_never_silently_truncates(): void
    {
        config([
            'request.exports.sync_row_limit' => 2,
            'request.exports.max_rows' => 3,
        ]);

        $user = $this->user(51, ['request.export', 'request.instance.view-own']);
        $planner = app(PlanRequestExport::class);

        InternalRequest::factory()->count(2)->create(['requester_id' => 51]);
        $sync = $planner->plan($user);
        $this->assertSame('sync', $sync->mode);
        $this->assertSame(2, $sync->authorizedRowCount);

        InternalRequest::factory()->create(['requester_id' => 51]);
        $queued = $planner->plan($user);
        $this->assertTrue($queued->shouldQueue());
        $this->assertSame(3, $queued->authorizedRowCount);

        InternalRequest::factory()->create(['requester_id' => 51]);

        $this->expectException(ValidationException::class);
        $planner->plan($user);
    }

    public function test_export_plan_uses_only_server_allowlisted_fields(): void
    {
        $user = $this->user(61, ['request.export', 'request.instance.view-own']);
        $plan = app(PlanRequestExport::class)->plan($user, [], ['request_number', 'title', 'payload_json', 'confidential_note']);

        $this->assertSame(['request_number', 'title'], $plan->fields);
        $this->assertNotContains('payload_json', $plan->fields);
        $this->assertNotContains('confidential_note', $plan->fields);
    }

    public function test_private_export_storage_rejects_public_disk_and_uses_opaque_paths(): void
    {
        $storage = app(RequestPrivateExportStorage::class);

        config(['request.files.disk' => 'local']);
        $this->assertSame('local', $storage->disk());
        $path = $storage->pathFor('01ARZ3NDEKTSV4RRFFQ69G5FAV', 'csv');
        $this->assertStringStartsWith('request/exports/01ARZ3NDEKTSV4RRFFQ69G5FAV/', $path);
        $this->assertStringEndsWith('.csv', $path);
        $this->assertStringNotContainsString('public', $path);

        config(['request.files.disk' => 'public']);
        $this->expectException(RuntimeException::class);
        $storage->disk();
    }

    private function user(int $id, array $permissions): object
    {
        return new class($id, $permissions) {
            public function __construct(private readonly int $id, private readonly array $permissions)
            {
            }

            public function getAuthIdentifier(): int
            {
                return $this->id;
            }

            public function checkPermissionTo(string $permission, string $guard): bool
            {
                return $guard === 'admin' && in_array($permission, $this->permissions, true);
            }
        };
    }
}
