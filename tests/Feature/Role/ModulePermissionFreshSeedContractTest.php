<?php

namespace Tests\Feature\Role;

use App\Modules\ModulePermissionManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class ModulePermissionFreshSeedContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_permission_sync_uses_persisted_ids_and_targets_super_admin(): void
    {
        // Occupy the first admin role id to reproduce the identity shape seen after
        // migrate:fresh when seeders create other roles before permission sync.
        $otherRole = Role::create(['name' => 'Request E2E · Nhân viên', 'guard_name' => 'admin']);

        $result = app(ModulePermissionManager::class)->syncAllActiveToSuperAdmin();

        $superAdmin = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->firstOrFail();

        $permissionIds = DB::table('role_has_permissions')
            ->where('role_id', $superAdmin->id)
            ->pluck('permission_id');

        $this->assertGreaterThan(0, $result['total']);
        $this->assertNotSame($otherRole->id, $superAdmin->id);
        $this->assertSame($permissionIds->count(), Permission::query()->whereIn('id', $permissionIds)->count());
        $this->assertSame(0, DB::table('role_has_permissions')->where('role_id', $otherRole->id)->count());
    }

    public function test_permission_sync_is_idempotent_after_permission_cache_reset(): void
    {
        $manager = app(ModulePermissionManager::class);
        $manager->syncAllActiveToSuperAdmin();
        $manager->forgetCache();

        $before = DB::table('role_has_permissions')->count();
        $manager->syncAllActiveToSuperAdmin();

        $this->assertSame($before, DB::table('role_has_permissions')->count());
    }
}
