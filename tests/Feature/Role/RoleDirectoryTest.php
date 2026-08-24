<?php

namespace Tests\Feature\Role;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Role\Contracts\RoleDirectory;
use Modules\Role\Data\RoleIdentity;
use Modules\Role\Exceptions\RoleDirectoryException;
use Modules\Role\Services\SpatieRoleDirectory;
use ReflectionClass;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleDirectoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_provider_binds_the_stable_role_directory_contract(): void
    {
        $this->assertInstanceOf(SpatieRoleDirectory::class, app(RoleDirectory::class));
        $this->assertTrue((new ReflectionClass(RoleIdentity::class))->isReadOnly());
    }

    public function test_it_finds_and_searches_only_admin_roles(): void
    {
        $admin = Role::create(['name' => 'Request Approver', 'guard_name' => 'admin']);
        Role::create(['name' => 'Request Web Role', 'guard_name' => 'web']);

        $directory = app(RoleDirectory::class);

        $this->assertSame($admin->id, $directory->findAdminRole($admin->id)?->id);
        $this->assertSame([$admin->id], array_column($directory->searchAdminRoles('Request', 10), 'id'));
        $this->assertNull($directory->findAdminRole(999999));
    }

    public function test_active_member_ids_are_ordered_and_filtered_through_user_directory(): void
    {
        $role = Role::create(['name' => 'Approver', 'guard_name' => 'admin']);
        $active = $this->createUser('Active', 'active-role@example.test', true);
        $inactive = $this->createUser('Inactive', 'inactive-role@example.test', false);
        $deleted = $this->createUser('Deleted', 'deleted-role@example.test', true, now());

        foreach ([$deleted, $active, $inactive] as $userId) {
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => config('auth.providers.users.model'),
                'model_id' => $userId,
            ]);
        }

        $this->assertSame([$active], app(RoleDirectory::class)->activeMemberIds($role->id, 10));
    }

    public function test_member_lookup_reports_safe_typed_failures(): void
    {
        $directory = app(RoleDirectory::class);

        try {
            $directory->activeMemberIds(999999, 10);
            $this->fail('A missing role must fail safely.');
        } catch (RoleDirectoryException $exception) {
            $this->assertSame('not_found', $exception->reason);
        }

        $webRole = Role::create(['name' => 'Wrong Guard', 'guard_name' => 'web']);

        try {
            $directory->activeMemberIds($webRole->id, 10);
            $this->fail('A wrong-guard role must fail safely.');
        } catch (RoleDirectoryException $exception) {
            $this->assertSame('wrong_guard', $exception->reason);
        }

        $this->expectException(InvalidArgumentException::class);
        $directory->searchAdminRoles('role', 0);
    }

    public function test_member_lookup_rejects_candidate_sets_over_the_bound(): void
    {
        $role = Role::create(['name' => 'Large Role', 'guard_name' => 'admin']);

        foreach (range(1, 2) as $sequence) {
            $userId = $this->createUser("User {$sequence}", "role-{$sequence}@example.test", true);
            DB::table('model_has_roles')->insert([
                'role_id' => $role->id,
                'model_type' => config('auth.providers.users.model'),
                'model_id' => $userId,
            ]);
        }

        try {
            app(RoleDirectory::class)->activeMemberIds($role->id, 1);
            $this->fail('An oversized candidate set must fail safely.');
        } catch (RoleDirectoryException $exception) {
            $this->assertSame('candidate_limit_exceeded', $exception->reason);
        }
    }

    private function createUser(string $name, string $email, bool $active, mixed $deletedAt = null): int
    {
        return (int) DB::table('users')->insertGetId([
            'name' => $name,
            'email' => $email,
            'password' => null,
            'is_active' => $active,
            'deleted_at' => $deletedAt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
