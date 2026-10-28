<?php

namespace Tests\Feature;

use Tests\TestCase;

final class AttendancePayrollDemoTest extends TestCase
{
    public function test_demo_route_and_blade_assets(): void
    {
        $response = $this->get('/demo/cham-cong-tinh-luong');
        $response->assertOk();
        $response->assertSee('id="app"', false);
        $response->assertSee('demo-assets/cham-cong-tinh-luong/js/core.js', false);
        $response->assertSee('demo-assets/cham-cong-tinh-luong/js/payroll.js', false);
        $response->assertSee('demo-assets/cham-cong-tinh-luong/js/extensions.js', false);
        $this->assertFileExists(public_path('demo-assets/cham-cong-tinh-luong/js/core.js'));
        $this->assertFileExists(public_path('demo-assets/cham-cong-tinh-luong/js/payroll.js'));
        $this->assertDirectoryDoesNotExist(public_path('demo/cham-cong-tinh-luong'));
    }
    public function test_sidebar_restores_icons_and_unifies_settings_without_changing_payroll_engine(): void
    {
        $adapter = file_get_contents(public_path('demo-assets/cham-cong-tinh-luong/js/extensions.js'));
        $sharedMenu = file_get_contents(public_path('demo/shared/sidebar-menu/menu-builder.js'));
        $engine = file_get_contents(public_path('demo-assets/cham-cong-tinh-luong/js/payroll.js'));
        $this->assertIsString($adapter);
        $this->assertIsString($sharedMenu);
        $this->assertIsString($engine);

        $this->assertStringContainsString("App.views.caidat.render = function(el)", $adapter);
        $this->assertStringContainsString('originalSettingsRender(el)', $adapter);
        $this->assertStringContainsString('renderDemoSettings(el)', $adapter);
        $this->assertStringContainsString("App.go('caidat')", $adapter);
        $this->assertStringNotContainsString("App.views['demo-settings'] =", $adapter);
        $this->assertStringContainsString('iconNode.innerHTML = ic(svgName)', $adapter);
        $this->assertStringContainsString("item.id === 'demo-settings'", $adapter);
        $this->assertStringContainsString("item.id = 'caidat'", $adapter);
        $this->assertStringContainsString("bridge.normalize(clone(config))", $sharedMenu);
        $this->assertStringContainsString("slug === 'cham-cong-tinh-luong'", $sharedMenu);
        $this->assertStringContainsString("views: { dashboard: vDash, nhanvien: vEmp, chamcong: vCham, bangluong: vLuong, thue: vThue, caidat: vSet }", $engine);
    }

}
