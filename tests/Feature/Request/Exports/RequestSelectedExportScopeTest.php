<?php

namespace Tests\Feature\Request\Exports;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Validation\ValidationException;
use Modules\Request\Application\Services\PlanRequestExport;
use Modules\Request\Application\Services\RequestExportQuery;
use Modules\Request\Models\InternalRequest;
use Tests\TestCase;

class RequestSelectedExportScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Artisan::call('migrate', ['--path' => 'Modules/Request/database/migrations', '--force' => true]);
    }

    public function test_selected_export_only_contains_selected_rows_inside_current_authorization_scope(): void
    {
        $selectedOwned = InternalRequest::factory()->create(['requester_id' => 301]);
        InternalRequest::factory()->create(['requester_id' => 301]);
        $selectedUnauthorized = InternalRequest::factory()->create(['requester_id' => 999]);

        $user = $this->user(301, ['request.export', 'request.instance.view-own']);
        $filters = [
            'request_public_ids' => [
                $selectedUnauthorized->public_id,
                $selectedOwned->public_id,
            ],
        ];

        $rows = app(RequestExportQuery::class)->queryFor($user, $filters)->get();
        $plan = app(PlanRequestExport::class)->plan($user, $filters);

        $this->assertSame([$selectedOwned->public_id], $rows->pluck('public_id')->all());
        $this->assertSame(1, $plan->authorizedRowCount);
        $this->assertSame(
            collect($filters['request_public_ids'])->sort()->values()->all(),
            $plan->filters['request_public_ids'],
        );
    }

    public function test_no_selection_keeps_existing_export_all_authorized_scope_semantics(): void
    {
        InternalRequest::factory()->count(3)->create(['requester_id' => 401]);
        InternalRequest::factory()->count(2)->create(['requester_id' => 999]);

        $user = $this->user(401, ['request.export', 'request.instance.view-own']);
        $plan = app(PlanRequestExport::class)->plan($user, []);

        $this->assertSame([], $plan->filters);
        $this->assertSame(3, $plan->authorizedRowCount);
    }

    public function test_selected_export_rejects_more_ids_than_the_bounded_visible_page_contract(): void
    {
        config(['request.settings.max_page_size' => 2]);
        $user = $this->user(501, ['request.export', 'request.instance.view-own']);
        $ids = InternalRequest::factory()->count(3)->create(['requester_id' => 501])->pluck('public_id')->all();

        $this->expectException(ValidationException::class);

        app(PlanRequestExport::class)->plan($user, ['request_public_ids' => $ids]);
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
