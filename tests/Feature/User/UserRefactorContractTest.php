<?php

declare(strict_types=1);

namespace Tests\Feature\User;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\Shared\Services\ImportExport\BaseImportExportService;
use Modules\User\Services\ImportExport;
use Modules\User\Services\UserService;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Tests\TestCase;

class UserRefactorContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_module_contract_and_handoff_exist(): void
    {
        $this->assertFileExists(base_path('docs/modules/User/MODULE.md'));
        $this->assertFileExists(base_path('docs/modules/User/COLLABORATION_HANDOFF.md'));
    }

    public function test_admin_user_ui_uses_canonical_inputs_pagination_and_export_selection_copy(): void
    {
        $table = File::get(base_path('Modules/User/resources/views/livewire/user-table.blade.php'));
        $form = File::get(base_path('Modules/User/resources/views/livewire/user-form.blade.php'));
        $pagination = File::get(base_path('Modules/User/resources/views/vendor/pagination/admin-users.blade.php'));

        $this->assertStringContainsString('border border-gray-300 bg-white', $table);
        $this->assertStringContainsString('<option value="100">100 dòng</option>', $table);
        $this->assertStringContainsString("links('User::vendor.pagination.admin-users')", $table);
        $this->assertStringContainsString('export chỉ các nhân sự đã chọn', mb_strtolower($table));
        $this->assertStringContainsString('không chọn dòng nào: export tất cả nhân sự theo bộ lọc hiện tại', mb_strtolower($table));
        $this->assertStringContainsString('backup đầy đủ credential bằng password_hash', mb_strtolower($table));
        $this->assertStringContainsString('border bg-white px-4 py-3', $form);
        $this->assertStringContainsString('bg-indigo-600', $pagination);
        $this->assertStringContainsString('bg-white', $pagination);
    }

    public function test_staff_export_uses_selected_ids_when_present_and_all_visible_filter_scope_when_empty(): void
    {
        $actor = $this->adminActor(['export_user']);
        $role = Role::findByName('staff', 'admin');

        $alice = User::factory()->create(['name' => 'Alice Export']);
        $bob = User::factory()->create(['name' => 'Bob Export']);
        $other = User::factory()->create(['name' => 'Other Person']);
        $alice->assignRole($role);
        $bob->assignRole($role);
        $other->assignRole($role);

        $service = app(UserService::class);

        $all = $service->exportStaff(['search' => 'Export', 'selected_ids' => []], $actor);
        $selected = $service->exportStaff(['search' => 'Export', 'selected_ids' => [$bob->id]], $actor);

        $this->assertEqualsCanonicalizing([$alice->id, $bob->id], $all->pluck('id')->all());
        $this->assertSame([$bob->id], $selected->pluck('id')->all());
    }

    public function test_non_super_admin_export_scope_never_exposes_super_admin(): void
    {
        $actor = $this->adminActor(['export_user']);
        $superRole = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $super = User::factory()->create(['name' => 'Hidden Root']);
        $super->assignRole($superRole);

        $visible = app(UserService::class)->exportStaff(['selected_ids' => [$super->id]], $actor);

        $this->assertTrue($visible->isEmpty());
    }

    public function test_user_import_export_does_not_create_unknown_role_catalog_entries(): void
    {
        $source = File::get(base_path('Modules/User/Services/ImportExport.php'));

        $this->assertStringNotContainsString('firstOrCreate([', $source);
        $this->assertStringContainsString('Vai trò không tồn tại trong Role catalog', $source);
        $this->assertStringContainsString('$user->syncRoles($adminRoles)', $source);
        $this->assertTrue(is_subclass_of(ImportExport::class, BaseImportExportService::class));
    }

    public function test_super_admin_backup_export_preserves_locked_state_and_password_hash(): void
    {
        $actor = $this->superAdminActor(['export_user']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'admin']);
        $passwordHash = Hash::make('roundtrip-secret');
        $user = User::factory()->create([
            'name' => 'Locked Backup User',
            'password' => $passwordHash,
            'is_active' => false,
        ]);
        $user->assignRole($staffRole);
        auth('admin')->login($actor);

        $service = app(ImportExport::class);
        $relativePath = $service->export([
            'selected_ids' => [$user->id],
            'include_password_hash' => true,
        ]);
        $absolutePath = $service->exportAbsolutePath($relativePath);

        try {
            $row = (new FastExcel)->import($absolutePath)->first();

            $this->assertSame(0, (int) $row['is_active']);
            $this->assertSame($passwordHash, $row['password_hash']);
            $this->assertArrayNotHasKey('password', $row);
        } finally {
            @unlink($absolutePath);
        }
    }

    public function test_super_admin_can_restore_password_hash_without_double_hashing(): void
    {
        $actor = $this->superAdminActor(['import_user']);
        $staffRole = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'admin']);
        $passwordHash = Hash::make('restore-secret');
        auth('admin')->login($actor);

        $path = storage_path('framework/testing/user-roundtrip-'.Str::uuid().'.xlsx');
        File::ensureDirectoryExists(dirname($path));

        (new FastExcel(collect([[
            'name' => 'Restored User',
            'email' => 'restored-user@example.test',
            'phone' => '0900000001',
            'password_hash' => $passwordHash,
            'is_active' => 0,
            'roles' => $staffRole->name,
        ]])))->export($path);

        try {
            $report = app(ImportExport::class)->import($path, ['mode' => 'update_or_create']);

            $this->assertTrue(
                $report['success'],
                json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
            );

            $restored = User::where('email', 'restored-user@example.test')->firstOrFail();

            $this->assertFalse((bool) $restored->is_active);
            $this->assertSame($passwordHash, $restored->getRawOriginal('password'));
            $this->assertTrue(Hash::check('restore-secret', $restored->password));
        } finally {
            @unlink($path);
        }
    }

    public function test_non_super_admin_cannot_export_password_hash_backup(): void
    {
        $actor = $this->adminActor(['export_user']);
        auth('admin')->login($actor);

        $this->expectException(HttpException::class);

        app(ImportExport::class)->export(['include_password_hash' => true]);
    }

    private function adminActor(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'staff', 'guard_name' => 'admin']);
        $actor = User::factory()->create();
        $actor->assignRole($role);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
            $role->givePermissionTo($permission);
        }

        return $actor->fresh();
    }

    private function superAdminActor(array $permissions): User
    {
        $role = Role::firstOrCreate(['name' => 'Super Admin', 'guard_name' => 'admin']);
        $actor = User::factory()->create();
        $actor->assignRole($role);

        foreach ($permissions as $permissionName) {
            $permission = Permission::firstOrCreate(['name' => $permissionName, 'guard_name' => 'admin']);
            $role->givePermissionTo($permission);
        }

        return $actor->fresh();
    }
}
