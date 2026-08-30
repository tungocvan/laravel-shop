<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminRoleStaffIdentityOwnershipContractTest extends TestCase
{
    public function test_admin_guard_uses_shared_user_provider_instead_of_legacy_admin_model(): void
    {
        $auth = file_get_contents(config_path('auth.php'));

        $this->assertStringContainsString("'admin' => [", $auth);
        $this->assertStringContainsString("'provider' => 'users'", $auth);
        $this->assertStringContainsString("App\\Models\\User::class", $auth);
        $this->assertStringNotContainsString('Modules\\Admin\\Models\\Admin', $auth);
    }

    public function test_legacy_admin_identity_model_remains_absent(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Admin/Models/Admin.php'));
    }

    public function test_admin_route_boundary_does_not_own_role_or_staff_runtime(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringNotContainsString('RoleController', $routes);
        $this->assertStringNotContainsString('StaffController', $routes);
        $this->assertStringNotContainsString("prefix('roles')", $routes);
        $this->assertStringNotContainsString("prefix('staff')", $routes);
    }

    public function test_role_module_keeps_canonical_role_routes_and_legacy_role_redirects(): void
    {
        $routes = file_get_contents(base_path('Modules/Role/routes/web.php'));

        $this->assertStringContainsString("prefix('admin/roles')", $routes);
        $this->assertStringContainsString("name('admin.role.')", $routes);
        $this->assertStringContainsString("permission:view_role,admin", $routes);
        $this->assertStringContainsString("permission:create_role,admin", $routes);
        $this->assertStringContainsString("permission:edit_role,admin", $routes);
        $this->assertStringContainsString("Route::redirect('/admin/role', '/admin/roles')", $routes);
        $this->assertStringContainsString("Route::redirect('/admin/role/create', '/admin/roles/create')", $routes);
        $this->assertStringContainsString("redirect('/admin/roles/'.\$id.'/edit')", $routes);
    }

    public function test_role_domain_keeps_service_and_livewire_boundaries(): void
    {
        $this->assertFileExists(base_path('Modules/Role/Services/RoleService.php'));
        $this->assertFileExists(base_path('Modules/Role/Services/SpatieRoleDirectory.php'));
        $this->assertFileExists(base_path('Modules/Role/Services/ImportExport.php'));
        $this->assertFileExists(base_path('Modules/Role/Livewire/RoleForm.php'));
        $this->assertFileExists(base_path('Modules/Role/Livewire/RoleTable.php'));
    }

    public function test_account_domain_keeps_employee_profile_identity_boundary(): void
    {
        $this->assertFileExists(base_path('Modules/Account/Models/EmployeeProfile.php'));
        $this->assertFileExists(base_path('Modules/Account/Models/User.php'));
        $this->assertFileExists(base_path('Modules/Account/Services/AccountService.php'));

        $accountRoutes = file_get_contents(base_path('Modules/Account/routes/web.php'));
        $this->assertStringContainsString("prefix('admin/accounts')", $accountRoutes);
        $this->assertStringContainsString("name('admin.accounts.')", $accountRoutes);
    }

    public function test_cleanup_does_not_change_auth_schema_or_role_schema_ownership(): void
    {
        $this->assertDirectoryExists(base_path('Modules/Role/database/migrations'));
        $this->assertDirectoryExists(base_path('Modules/Account/database/migrations'));
        $this->assertFileExists(base_path('config/auth.php'));
    }

    public function test_p0_database_service_remains_quarantined(): void
    {
        $this->assertFileExists(base_path('Modules/Admin/Services/DatabaseService.php'));

        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $this->assertStringNotContainsString('DatabaseController', $routes);
    }
}
