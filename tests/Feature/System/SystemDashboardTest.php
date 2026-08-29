<?php

namespace Tests\Feature\System;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Schema;
use Modules\System\Data\SystemDashboardData;
use Modules\System\Models\Setting;
use Modules\System\Services\SystemDashboardService;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class SystemDashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_route_uses_the_expected_contract_and_preserves_the_existing_index(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.dashboard');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/dashboard', $route->uri());
        $this->assertContains('auth:admin', $route->getAction('middleware'));
        $this->assertContains('permission:system.manage,admin', $route->getAction('middleware'));

        $indexRoute = Route::getRoutes()->getByName('admin.system.index');

        $this->assertNotNull($indexRoute);
        $this->assertSame('admin/system', $indexRoute->uri());
        $this->assertSame(
            'Modules\\System\\Http\\Controllers\\SystemController@index',
            $indexRoute->getActionName(),
        );

        $this->get(route('admin.system.dashboard'))->assertRedirect();

        $admin = User::factory()->create();
        $this->actingAs($admin, 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertForbidden();

        $viewer = $this->adminWithPermissions(['system.manage']);
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertOk();

        $superAdmin = User::factory()->create();
        $superAdmin->assignRole(Role::findOrCreate('Super Admin', 'admin'));

        $this->actingAs($superAdmin->fresh(), 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertOk();
    }

    public function test_dashboard_renders_permission_aware_workspaces_without_secrets_or_remote_calls(): void
    {
        Http::preventStrayRequests();

        config([
            'system.google_drive.client_id' => 'drive-client-secret',
            'system.google_drive.client_secret' => 'drive-client-secret-value',
            'system.google_drive.redirect_uri' => 'https://private.example.test/oauth/callback',
        ]);

        Setting::query()->create([
            'key' => 'cloud.google_drive.refresh_token',
            'value' => Crypt::encryptString('drive-refresh-token-secret'),
            'group_name' => 'cloud_storage',
        ]);
        Setting::query()->create([
            'key' => 'cloud.google_drive.auto.enabled',
            'value' => '1',
            'group_name' => 'cloud_storage',
            'type' => 'boolean',
        ]);
        Setting::query()->create([
            'key' => 'cloud.google_drive.auto.last_status',
            'value' => 'failed',
            'group_name' => 'cloud_storage',
        ]);
        Setting::query()->create([
            'key' => 'cloud.google_drive.auto.last_run_at',
            'value' => now()->subMinute()->toIso8601String(),
            'group_name' => 'cloud_storage',
        ]);
        Setting::query()->create([
            'key' => 'cloud.google_drive.auto.last_message',
            'value' => 'raw-cloud-backup-error-secret',
            'group_name' => 'cloud_storage',
        ]);
        Setting::query()->create([
            'key' => 'cloud.google_drive.email',
            'value' => 'drive-owner-secret@example.test',
            'group_name' => 'cloud_storage',
        ]);

        $viewer = $this->adminWithPermissions(['system.manage']);
        $this->actingAs($viewer, 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertOk()
            ->assertSee('Dashboard hệ thống')
            ->assertSee('System workspace')
            ->assertDontSee('href="'.route('admin.system.settings.env').'"', false)
            ->assertDontSee('href="'.route('admin.system.modules').'"', false)
            ->assertDontSee('href="'.route('admin.system.database.index').'"', false);

        $operator = $this->adminWithPermissions([
            'system.manage',
            'system.settings.view',
            'system.env.view',
            'system.modules.view',
            'system.commands.run',
            'database.view',
        ]);

        $response = $this->actingAs($operator, 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertOk()
            ->assertSee('href="'.route('admin.system.settings.env').'"', false)
            ->assertSee('href="'.route('admin.system.modules').'"', false)
            ->assertSee('href="'.route('admin.system.database.index').'"', false)
            ->assertSee('Google Drive')
            ->assertSee('OAuth: đã cấu hình')
            ->assertSee('Kết nối: đã lưu')
            ->assertSee('Lần cloud backup gần nhất thất bại');

        foreach ([
            'drive-client-secret',
            'drive-client-secret-value',
            'private.example.test',
            'drive-refresh-token-secret',
            'raw-cloud-backup-error-secret',
            'drive-owner-secret@example.test',
            'cloud.google_drive.refresh_token',
            'cloud.google_drive.auto.last_message',
        ] as $secret) {
            $response->assertDontSee($secret, false);
        }
    }

    public function test_dashboard_service_returns_a_bounded_safe_dto_with_constant_query_count(): void
    {
        config([
            'system.google_drive.client_id' => 'dto-client-secret',
            'system.google_drive.client_secret' => 'dto-client-secret-value',
            'system.google_drive.redirect_uri' => 'https://dto-private.example.test/oauth/callback',
        ]);

        Setting::query()->create([
            'key' => 'cloud.google_drive.refresh_token',
            'value' => Crypt::encryptString('dto-refresh-token-secret'),
            'group_name' => 'cloud_storage',
        ]);

        $admin = $this->adminWithPermissions([
            'system.manage',
            'system.settings.view',
            'system.env.view',
            'system.modules.view',
            'system.commands.run',
            'database.view',
        ]);
        $service = app(SystemDashboardService::class);
        $dashboard = $service->forUser($admin);
        $serialized = json_encode($dashboard, JSON_THROW_ON_ERROR);

        $this->assertInstanceOf(SystemDashboardData::class, $dashboard);
        $this->assertLessThanOrEqual(8, count($dashboard->workspaces));
        $this->assertLessThanOrEqual(5, count($dashboard->warnings));
        $this->assertSame(count($dashboard->workspaces), $dashboard->metrics['workspaces']['visible']);

        foreach ([
            'dto-client-secret',
            'dto-client-secret-value',
            'dto-private.example.test',
            'dto-refresh-token-secret',
            'refresh_token',
            'client_secret',
            'last_message',
            'raw_exception',
            'private_path',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $serialized);
        }

        // Warm permission relationships before comparing the bounded dashboard queries.
        $service->forUser($admin);
        DB::flushQueryLog();
        DB::enableQueryLog();
        $service->forUser($admin);
        $baselineQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        Setting::query()->insert(collect(range(1, 40))->map(fn (int $index): array => [
            'key' => 'dashboard.unrelated.'.$index,
            'value' => 'sensitive-unrelated-value-'.$index,
            'group_name' => 'dashboard-test',
            'type' => 'text',
            'created_at' => now(),
            'updated_at' => now(),
        ])->all());

        DB::flushQueryLog();
        DB::enableQueryLog();
        $expanded = $service->forUser($admin);
        $expandedQueryCount = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertGreaterThan(0, $baselineQueryCount);
        $this->assertSame($baselineQueryCount, $expandedQueryCount);
        $this->assertStringNotContainsString(
            'sensitive-unrelated-value-',
            json_encode($expanded, JSON_THROW_ON_ERROR),
        );
    }

    public function test_dashboard_renders_unavailable_states_without_querying_missing_tables(): void
    {
        $viewer = $this->adminWithPermissions([
            'system.manage',
            'system.settings.view',
            'system.env.view',
            'database.view',
        ]);

        Schema::partialMock()
            ->shouldReceive('hasTable')
            ->andReturn(false);

        $this->actingAs($viewer, 'admin')
            ->get(route('admin.system.dashboard'))
            ->assertOk()
            ->assertSee('Kho thiết lập hệ thống chưa sẵn sàng')
            ->assertSee('Kho queue chưa đầy đủ')
            ->assertSee('Metadata database chưa sẵn sàng')
            ->assertSee('Chưa sẵn sàng');
    }

    public function test_system_workspaces_expose_a_permission_aware_dashboard_return_link(): void
    {
        $viewer = $this->adminWithPermissions(['system.manage']);

        $this->actingAs($viewer, 'admin');
        $rendered = view('System::partials.dashboard-return-link')->render();

        $this->assertStringContainsString(route('admin.system.dashboard'), $rendered);
        $this->assertStringContainsString('Quay về Dashboard', $rendered);

        $settingsViewer = $this->adminWithPermissions(['system.settings.view']);

        $this->actingAs($settingsViewer, 'admin');
        $renderedWithoutManagePermission = view('System::partials.dashboard-return-link')->render();

        $this->assertStringNotContainsString(
            route('admin.system.dashboard'),
            $renderedWithoutManagePermission,
        );

        foreach ([
            'system.blade.php',
            'pages/settings/env.blade.php',
            'pages/settings/index.blade.php',
            'pages/settings/modules.blade.php',
            'pages/settings/artisan.blade.php',
            'pages/settings/scripts.blade.php',
            'pages/database.blade.php',
            'pages/database-backup-restore.blade.php',
        ] as $workspaceView) {
            $source = file_get_contents(base_path('Modules/System/resources/views/'.$workspaceView));

            $this->assertIsString($source);
            $this->assertStringContainsString(
                "@include('System::partials.dashboard-return-link')",
                $source,
                $workspaceView,
            );
        }
    }

    /**
     * @param  array<int, string>  $permissions
     */
    private function adminWithPermissions(array $permissions): User
    {
        $admin = User::factory()->create();

        foreach ($permissions as $permission) {
            $admin->givePermissionTo(Permission::findOrCreate($permission, 'admin'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $admin->fresh();
    }
}
