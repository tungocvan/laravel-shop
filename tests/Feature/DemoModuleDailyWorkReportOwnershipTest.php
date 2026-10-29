<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\DailyWorkReport\DailyWorkReportController;
use Modules\Demo\Http\Controllers\Projects\DailyWorkReport\DailyWorkReportAssetController;
use Tests\TestCase;

final class DemoModuleDailyWorkReportOwnershipTest extends TestCase
{
    public function test_ui_route_and_blade_are_owned_by_demo_module(): void
    {
        $route=Route::getRoutes()->getByName('demo.daily-work-report');
        $this->assertNotNull($route);
        $this->assertSame('demo/bao-cao-cong-viec-hang-ngay', $route->uri());
        $this->assertSame(DailyWorkReportController::class, $route->getActionName());
        $this->assertTrue(view()->exists('demo::projects.daily-work-report.index'));
        $this->get('/demo/bao-cao-cong-viec-hang-ngay')->assertOk()
            ->assertSee('id="page-dashboard"', false)
            ->assertSee('id="page-settings"', false)
            ->assertSee('data-demo-menu="bao-cao-cong-viec-hang-ngay"', false);
    }

    public function test_offline_html_export_template_is_preserved(): void
    {
        $this->assertSame(DailyWorkReportAssetController::class, Route::getRoutes()->getByName('demo.daily-work-report.asset')->getActionName());
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/daily-work-report/original.html'));
        $template=file_get_contents(base_path('Modules/Demo/resources/assets/projects/daily-work-report/original.html'));
        $this->assertStringContainsString('<script id="du-lieu" type="application/json">', $template);
        $this->get('/demo-assets/bao-cao-cong-viec-hang-ngay/original.html')->assertOk()
            ->assertHeader('X-Content-Type-Options', 'nosniff');
        $this->get('/demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js')->assertOk();
        $this->get('/demo-assets/bao-cao-cong-viec-hang-ngay/unknown.js')->assertNotFound();
    }

    public function test_five_theme_cards_preserve_existing_storage_key(): void
    {
        $script=file_get_contents(base_path('Modules/Demo/resources/assets/projects/daily-work-report/js/app.js'));
        $style=file_get_contents(base_path('Modules/Demo/resources/assets/projects/daily-work-report/css/app.css'));
        $this->assertStringContainsString('daily_work_report_theme_v1', $script);
        foreach (['original','ruby','ocean','emerald','indigo'] as $theme) {
            $this->assertStringContainsString($theme.':', $script);
        }
        $this->assertStringContainsString('grid-template-columns:repeat(5,minmax(0,1fr))', $style);
    }
}
