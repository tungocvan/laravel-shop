<?php

namespace Tests\Feature;

use Tests\TestCase;

final class DemoModuleAttendancePayrollOwnershipTest extends TestCase
{
    public function test_attendance_payroll_routes_are_owned_by_demo_module(): void
    {
        foreach (['demo.attendance-payroll', 'demo.attendance-payroll.datasets', 'demo.attendance-payroll.datasets.show', 'demo.attendance-payroll.datasets.store'] as $name) {
            $route = app('router')->getRoutes()->getByName($name);
            $this->assertNotNull($route, $name);
            $this->assertStringContainsString('Modules\\Demo\\Http\\Controllers\\Projects\\AttendancePayroll\\', $route->getActionName());
        }
    }

    public function test_original_browser_assets_are_served_from_module(): void
    {
        foreach (['css/app.css', 'css/extensions.css', 'js/core.js', 'js/payroll.js', 'js/extensions.js', 'shared/menu-editor.css', 'shared/menu-editor.js'] as $asset) {
            $response = $this->get('/demo-assets/cham-cong-tinh-luong/'.$asset);
            $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/cham-cong-tinh-luong/unknown.js')->assertNotFound();
    }

    public function test_payroll_theme_and_menu_contract(): void
    {
        $adapter = file_get_contents(base_path('Modules/Demo/resources/assets/projects/attendance-payroll/js/extensions.js'));
        $layout = file_get_contents(base_path('Modules/Demo/resources/views/projects/attendance-payroll/layouts/app.blade.php'));
        $this->assertStringContainsString("['original', 'ruby', 'ocean', 'emerald', 'indigo']", $adapter);
        $this->assertStringContainsString('payroll-palette', $adapter);
        $this->assertStringContainsString('data-demo-menu="cham-cong-tinh-luong"', $layout);
        $this->assertStringContainsString('shared/menu-editor.js', $layout);
    }
}
