<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminOwnershipBoundaryContractTest extends TestCase
{
    public function test_admin_manifest_remains_a_shell_module(): void
    {
        $manifest = require base_path('Modules/Admin/config/module.php');

        $this->assertSame('Admin', $manifest['name'] ?? null);
        $this->assertSame('shell', $manifest['type'] ?? null);
        $this->assertSame(['Auth', 'User', 'Role'], $manifest['depends'] ?? null);
    }

    public function test_active_admin_routes_are_limited_to_canonical_shell_controllers(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertNotFalse($routes);

        preg_match_all(
            '/^use Modules\\\\Admin\\\\Http\\\\Controllers\\\\([A-Za-z0-9_]+);$/m',
            $routes,
            $matches
        );

        $controllers = $matches[1] ?? [];
        sort($controllers);

        $expected = [
            'AdminController',
            'DashboardController',
            'MenuController',
            'ProfileController',
        ];
        sort($expected);

        $this->assertSame($expected, $controllers);
        $this->assertStringContainsString("Route::middleware(['web', 'auth:admin'])", $routes);
    }

    public function test_layout_header_is_canonical_and_legacy_admin_header_entry_is_removed(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Admin/Http/Controllers/AdminController.php'));

        $this->assertNotFalse($routes);
        $this->assertNotFalse($controller);
        $this->assertStringContainsString("Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header')", $routes);
        $this->assertStringNotContainsString("Route::get('/admin-header'", $routes);
        $this->assertStringNotContainsString('function adminHeader(', $controller);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/pages/admin/header/index.blade.php'));
    }

    public function test_sidebar_menu_configuration_is_canonical_admin_shell_surface(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));
        $layoutSection = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));
        $manifest = require base_path('Modules/Admin/config/module.php');

        $this->assertNotFalse($routes);
        $this->assertNotFalse($layoutSection);
        $this->assertStringContainsString("Route::prefix('menus')->name('menus.')", $routes);
        $this->assertStringContainsString("[MenuController::class, 'index']", $routes);
        $this->assertStringContainsString('permission:admin.menu.view,admin', $routes);
        $this->assertStringContainsString('permission:admin.menu.create,admin', $routes);
        $this->assertStringContainsString('permission:admin.menu.update,admin', $routes);
        $this->assertStringContainsString("\$section === 'sidebar'", $layoutSection);
        $this->assertStringContainsString("route('admin.menus.index')", $layoutSection);
        $this->assertStringContainsString('Quản lý menu Sidebar', $layoutSection);

        $permissions = $manifest['permissions'] ?? [];
        $this->assertContains('admin.menu.view', $permissions);
        $this->assertContains('admin.menu.create', $permissions);
        $this->assertContains('admin.menu.update', $permissions);
        $this->assertContains('admin.menu.delete', $permissions);
        $this->assertContains('admin.menu.restore', $permissions);
        $this->assertContains('admin.menu.import', $permissions);
        $this->assertContains('admin.menu.export', $permissions);
    }

    public function test_admin_api_surface_stays_closed_without_an_explicit_contract(): void
    {
        $apiRoutes = file_get_contents(base_path('Modules/Admin/routes/api.php'));

        $this->assertNotFalse($apiRoutes);
        $this->assertStringNotContainsString('Route::', $apiRoutes);
    }
}
