<?php
namespace Tests\Feature;
use Tests\TestCase;
class DailyWorkReportDemoTest extends TestCase
{
    public function test_daily_work_report_demo_renders(): void
    {
        $this->get('/demo/bao-cao-cong-viec-hang-ngay')
            ->assertOk()
            ->assertSee('page-dashboard', false)
            ->assertSee('page-settings', false)
            ->assertSee('demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js', false);
    }
    public function test_demo_assets_do_not_shadow_ui_route(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('demo/bao-cao-cong-viec-hang-ngay'));
        $this->assertFileExists(public_path('demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js'));
    }
}
