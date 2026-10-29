<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\EnterpriseManagementReport\EnterpriseManagementReportController;
use Modules\Demo\Http\Controllers\Projects\EnterpriseManagementReport\EnterpriseManagementReportAssetController;
use Tests\TestCase;

final class DemoModuleEnterpriseManagementReportOwnershipTest extends TestCase
{
    public function test_enterprise_report_ui_and_assets_are_module_owned(): void
    {
        $route=Route::getRoutes()->getByName('demo.enterprise-management-report');
        $this->assertNotNull($route);
        $this->assertSame('demo/bao-cao-quan-tri-doanh-nghiep', $route->uri());
        $this->assertSame(EnterpriseManagementReportController::class, $route->getActionName());
        $this->assertTrue(view()->exists('demo::projects.enterprise-management-report.index'));
        $this->assertSame(EnterpriseManagementReportAssetController::class, Route::getRoutes()->getByName('demo.enterprise-management-report.asset')->getActionName());
        $this->get('/demo/bao-cao-quan-tri-doanh-nghiep')->assertOk()
            ->assertSee('id="sb"',false)
            ->assertSee('id="du-lieu"',false);
        $this->get('/demo-assets/bao-cao-quan-tri-doanh-nghiep/js/app.js')->assertOk();
        $this->get('/demo-assets/bao-cao-quan-tri-doanh-nghiep/js/preferences.js')->assertOk();
        $this->get('/demo-assets/bao-cao-quan-tri-doanh-nghiep/original.html')->assertOk()
            ->assertHeader('X-Content-Type-Options','nosniff');
        $this->get('/demo-assets/bao-cao-quan-tri-doanh-nghiep/unknown.js')->assertNotFound();
    }

    public function test_five_theme_previews_and_menu_presets_remain_available(): void
    {
        $script=file_get_contents(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/js/preferences.js'));
        $css=file_get_contents(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/css/app.css'));
        foreach (['original','ruby','ocean','emerald','indigo'] as $theme) {
            $this->assertStringContainsString($theme.':', $script);
        }
        $this->assertStringContainsString('enterprise-theme-swatch', $script);
        $this->assertStringContainsString('aria-pressed', $script);
        $this->assertStringContainsString('grid-template-columns:repeat(5', $css);
        $this->assertStringContainsString('/demo/bao-cao-quan-tri-doanh-nghiep/menu', $script);
        $this->assertStringContainsString('Lưu lên server', $script);
    }
    public function test_editor_uses_shared_demo_tab_layout_without_removing_menu_persistence(): void
    {
        $script=file_get_contents(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/js/preferences.js'));
        $css=file_get_contents(base_path('Modules/Demo/resources/assets/projects/enterprise-management-report/css/app.css'));
        foreach (['Menu', 'Header', 'Footer', 'Thư viện JSON'] as $label) {
            $this->assertStringContainsString($label, $script);
        }
        foreach (['enterprise-editor-tabs', 'enterprise-editor-pane', 'aria-selected', 'selectTab(activeTab)', 'enterprise-server-library'] as $token) {
            $this->assertStringContainsString($token, $script);
        }
        foreach (['enterprise-editor-heading', 'enterprise-editor-tabs', '.enterprise-editor-pane[hidden]'] as $token) {
            $this->assertStringContainsString($token, $css);
        }
        foreach (['sidebar_v1', 'protectedMenu(', 'downloadMenu(', 'normalizeMenu(draft)'] as $token) {
            $this->assertStringContainsString($token, $script);
        }
    }

}
