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
}
