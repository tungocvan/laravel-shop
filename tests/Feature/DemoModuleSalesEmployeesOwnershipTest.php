<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class DemoModuleSalesEmployeesOwnershipTest extends TestCase
{
    public function test_sales_module_renders_all_sections_and_shared_editor(): void
    {
        $this->get('/demo/doanh-so-nhan-vien')->assertOk()
            ->assertSee('id="page-dashboard"', false)
            ->assertSee('id="page-settings"', false)
            ->assertSee('data-demo-menu="doanh-so-nhan-vien"', false)
            ->assertSee('demo-assets/doanh-so-nhan-vien/shared/menu-editor.js', false);
    }

    public function test_sales_assets_are_allowlisted(): void
    {
        foreach (['css/app.css','js/app.js','shared/menu-editor.css','shared/menu-editor.js'] as $asset) {
            $this->get('/demo-assets/doanh-so-nhan-vien/'.$asset)->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
        }
        $this->get('/demo-assets/doanh-so-nhan-vien/.env')->assertNotFound();
    }

    public function test_sales_dataset_endpoint_and_five_theme_definitions(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/doanh-so-nhan-vien/datasets')->assertOk();
        $js = file_get_contents(base_path('Modules/Demo/resources/assets/projects/sales-employees/js/app.js'));
        foreach (['original:', 'ruby:', 'ocean:', 'emerald:', 'indigo:', 'sales_employees_theme_v1', 'localStorage'] as $needle) {
            $this->assertStringContainsString($needle, $js);
        }
    }
}
