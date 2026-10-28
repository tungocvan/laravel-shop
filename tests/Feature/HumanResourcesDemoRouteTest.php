<?php
namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class HumanResourcesDemoRouteTest extends TestCase
{
    public function test_standalone_hr_demo_renders_original_dom_and_new_controls(): void
    {
        $this->get('/demo/quan-ly-nhan-su')->assertOk()
            ->assertSee('page-dashboard')->assertSee('page-employees')
            ->assertSee('savedData')->assertSee('hrTheme')
            ->assertSee('hrLibrary')->assertSee('data-demo-menu');
    }

    public function test_hr_script_loads_after_embedded_data_and_markup(): void
    {
        $html = $this->get('/demo/quan-ly-nhan-su')->assertOk()->getContent();
        $this->assertLessThan(strpos($html, 'demo-assets/quan-ly-nhan-su/js/app.js'), strpos($html, 'id="savedData"'));
        $this->assertLessThan(strpos($html, 'demo-assets/quan-ly-nhan-su/js/app.js'), strpos($html, 'id="stats"'));
        $js = file_get_contents(public_path('demo-assets/quan-ly-nhan-su/js/app.js'));
        $this->assertStringNotContainsString('\\\\nconst KEY', substr($js, 0, 180));
    }

    public function test_settings_workspace_reuses_existing_route_and_menu_editor(): void
    {
        $this->get('/demo/quan-ly-nhan-su')->assertOk()
            ->assertSee('id="page-settings"', false)
            ->assertSee('Cài đặt & dữ liệu', false)
            ->assertSee('data-hr-theme-choice="ruby"', false)
            ->assertSee('data-hr-theme-choice="ocean"', false)
            ->assertSee('data-hr-theme-choice="emerald"', false)
            ->assertSee('data-hr-theme-choice="indigo"', false)
            ->assertSee('data-open-menu', false)
            ->assertSee('hrSettingsCount');
        $adapter = file_get_contents(public_path('demo-assets/quan-ly-nhan-su/js/menu-adapter.js'));
        $this->assertStringContainsString("href:'#settings'", $adapter);
        $this->assertStringContainsString("goPage('settings')", $adapter);
    }

    public function test_assets_exist(): void
    {
        foreach (['js/app.js','js/menu-adapter.js','css/app.css'] as $path) {
            $this->assertFileExists(public_path('demo-assets/quan-ly-nhan-su/'.$path));
        }
    }

    public function test_dataset_library_starts_empty_and_blocks_traversal(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/quan-ly-nhan-su/datasets')->assertOk()->assertJson(['datasets'=>[]]);
        $this->getJson('/demo/quan-ly-nhan-su/datasets/%2E%2E')->assertNotFound();
    }

    public function test_dataset_write_fails_closed_without_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/quan-ly-nhan-su/datasets', ['name'=>'Bản sao','data'=>[]])
            ->assertStatus(503);
    }

    public function test_dataset_write_requires_valid_secret_and_schema(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('x', 24));
        $this->postJson('/demo/quan-ly-nhan-su/datasets', ['name'=>'Bản sao','data'=>[]])
            ->assertForbidden();
        $this->withHeader('X-Demo-Backup-Code', str_repeat('x',24))
            ->postJson('/demo/quan-ly-nhan-su/datasets', ['name'=>'Bản sao','data'=>[['code'=>'NV001']]])
            ->assertStatus(422);
    }

    public function test_dataset_write_and_read_roundtrip(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('x', 24));
        $employee = [
            'code'=>'NV001','name'=>'Nguyễn Văn A','gender'=>'Nam','dob'=>'1990-01-01',
            'dept'=>'IT','role'=>'Lập trình viên','phone'=>'0123456789',
            'email'=>'a@example.test','start'=>'2020-01-01','salary'=>12000000,'status'=>'Đang làm',
        ];
        $response=$this->withHeader('X-Demo-Backup-Code',str_repeat('x',24))
            ->postJson('/demo/quan-ly-nhan-su/datasets', ['name'=>'Nhân sự mẫu','data'=>[$employee]]);
        $response->assertCreated()->assertJsonPath('employees',1);
        $id=$response->json('id');
        $this->getJson('/demo/quan-ly-nhan-su/datasets/'.$id)
            ->assertOk()->assertJsonPath('data.0.code','NV001');
        $this->getJson('/demo/quan-ly-nhan-su/datasets')->assertOk()->assertJsonCount(1,'datasets');
    }

    public function test_hr_menu_write_requires_operator_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('z', 24));
        $this->postJson('/demo/quan-ly-nhan-su/menu', ['config'=>[]])->assertForbidden();
    }

    public function test_hr_assets_include_four_themes_and_json_import_export(): void
    {
        $js = file_get_contents(public_path('demo-assets/quan-ly-nhan-su/js/app.js'));
        foreach (['ruby','ocean','emerald','indigo','hrImportFile','hrStoreLibrary','hrSetTheme','hrRestore'] as $marker) {
            $this->assertStringContainsString($marker, $js);
        }
    }

    public function test_shared_menu_api_has_hr_namespace(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/quan-ly-nhan-su/menu')->assertOk()->assertJson(['config'=>null]);
        $this->getJson('/demo/quan-ly-nhan-su/menu/presets')->assertOk()->assertJson(['presets'=>[]]);
    }
}
