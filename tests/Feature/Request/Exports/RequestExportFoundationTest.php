<?php

namespace Tests\Feature\Request\Exports;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\PlanRequestExport;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Models\InternalRequest;
use Modules\Request\Models\RequestGroup;
use Modules\Request\Models\RequestType;
use Modules\Request\Models\RequestTypeVersion;
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

    public function test_report_filters_keep_group_type_and_local_date_boundaries_inside_authorized_scope(): void
    {
        config(['app.timezone' => 'Asia/Ho_Chi_Minh']);

        $group = RequestGroup::factory()->create(['name' => 'Hành chính']);
        $otherGroup = RequestGroup::factory()->create(['name' => 'Nhân sự']);
        $type = RequestType::factory()->for($group, 'group')->create(['name' => 'Đề nghị thiết bị']);
        $otherType = RequestType::factory()->for($otherGroup, 'group')->create(['name' => 'Đề nghị nghỉ phép']);
        $version = RequestTypeVersion::factory()->for($type, 'type')->create();
        $otherVersion = RequestTypeVersion::factory()->for($otherType, 'type')->create();

        $included = InternalRequest::factory()->create([
            'request_type_id' => $type->id,
            'request_type_version_id' => $version->id,
            'requester_id' => 81,
            'created_at' => CarbonImmutable::parse('2026-08-25 16:59:59', 'UTC'),
        ]);
        InternalRequest::factory()->create([
            'request_type_id' => $type->id,
            'request_type_version_id' => $version->id,
            'requester_id' => 81,
            'created_at' => CarbonImmutable::parse('2026-08-25 17:00:00', 'UTC'),
        ]);
        InternalRequest::factory()->create([
            'request_type_id' => $otherType->id,
            'request_type_version_id' => $otherVersion->id,
            'requester_id' => 81,
            'created_at' => CarbonImmutable::parse('2026-08-25 10:00:00', 'UTC'),
        ]);
        InternalRequest::factory()->create([
            'request_type_id' => $type->id,
            'request_type_version_id' => $version->id,
            'requester_id' => 999,
            'created_at' => CarbonImmutable::parse('2026-08-25 10:00:00', 'UTC'),
        ]);

        $user = $this->user(81, ['request.export', 'request.instance.view-own']);
        $filters = [
            'group_public_id' => $group->public_id,
            'type_public_id' => $type->public_id,
            'created_from' => '2026-08-25',
            'created_to' => '2026-08-25',
        ];

        $rows = app(RequestExportQuery::class)->queryFor($user, $filters)->get();
        $plan = app(PlanRequestExport::class)->plan($user, $filters);

        $this->assertSame([$included->id], $rows->pluck('id')->all());
        $this->assertSame($filters, $plan->filters);
        $this->assertSame(1, $plan->authorizedRowCount);
        $this->assertSame('Hành chính', $rows->firstOrFail()->type->group->name);
    }

    public function test_export_planner_rejects_invalid_filters_instead_of_broadening_scope(): void
    {
        $user = $this->user(91, ['request.export', 'request.instance.view-own']);

        $this->expectException(ValidationException::class);

        app(PlanRequestExport::class)->plan($user, ['status' => 'not-a-real-status']);
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
        return new class($id, $permissions)
        {
            public function __construct(private readonly int $id, private readonly array $permissions) {}

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
