<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SalesDemoDatasetLibraryTest extends TestCase
{
    private function payload(): array
    {
        return ['name' => 'Doanh số 2026', 'data' => [
            'nv' => [['id' => 'n1', 'name' => 'Nhân viên A']],
            'sp' => [['id' => 's1', 'name' => 'Sản phẩm A']],
            'ban' => [['id' => 'b1', 'nv' => 'n1', 'sp' => 's1', 'qty' => 2]],
        ]];
    }

    public function test_server_writes_are_disabled_without_manager_code(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/doanh-so-nhan-vien/datasets', $this->payload())->assertStatus(503);
        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');
        $this->withHeader('X-Demo-Backup-Code', 'incorrect')
            ->postJson('/demo/doanh-so-nhan-vien/datasets', $this->payload())->assertForbidden();
        $this->assertSame([], Storage::disk('local')->files('demo-datasets/sales-employees'));
    }

    public function test_authorized_backup_can_be_listed_and_loaded_without_authentication(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');
        $response = $this->withHeader('X-Demo-Backup-Code', 'correct-private-code-12345')
            ->postJson('/demo/doanh-so-nhan-vien/datasets', $this->payload());
        $response->assertCreated()->assertJsonPath('name', 'Doanh số 2026');
        $id = $response->json('id');
        Storage::disk('local')->assertExists('demo-datasets/sales-employees/'.$id.'.json');
        $this->getJson('/demo/doanh-so-nhan-vien/datasets')->assertOk()
            ->assertJsonPath('datasets.0.name', 'Doanh số 2026')
            ->assertJsonPath('datasets.0.nv', 1);
        $this->getJson('/demo/doanh-so-nhan-vien/datasets/'.$id)->assertOk()
            ->assertJsonPath('ban.0.id', 'b1');
    }

    public function test_invalid_dataset_cannot_be_saved_or_read(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');
        $bad = $this->payload();
        $bad['data']['nv'][0]['id'] = '';
        $this->withHeader('X-Demo-Backup-Code', 'correct-private-code-12345')
            ->postJson('/demo/doanh-so-nhan-vien/datasets', $bad)->assertUnprocessable();
        Storage::disk('local')->put('demo-datasets/sales-employees/broken.json', '{invalid');
        $this->getJson('/demo/doanh-so-nhan-vien/datasets/broken')->assertNotFound();
        $this->getJson('/demo/doanh-so-nhan-vien/datasets')->assertJsonCount(0, 'datasets');
    }

    public function test_settings_exposes_library_and_confirmation_modal(): void
    {
        $this->get('/demo/doanh-so-nhan-vien')->assertOk()
            ->assertSee('name="csrf-token"', false);
        $script = file_get_contents(public_path('demo/sales-employees/js/app.js'));
        $this->assertStringContainsString('renderSalesDemoLibrary()', $script);
        $this->assertStringContainsString('salesBackupModal', $script);
        $this->assertStringContainsString("!=='YES'", $script);
        $this->assertStringContainsString('X-Demo-Backup-Code', $script);
    }

    public function test_four_themes_have_persisted_independent_palette_and_responsive_selector(): void
    {
        $js = file_get_contents(public_path('demo/sales-employees/js/app.js'));
        $css = file_get_contents(public_path('demo/sales-employees/css/app.css'));
        foreach (['ruby', 'ocean', 'emerald', 'indigo'] as $theme) {
            $this->assertStringContainsString($theme.':{label:', $js);
        }
        $this->assertStringContainsString("sales_employees_theme_v1", $js);
        $this->assertStringContainsString('localStorage.setItem(SALES_THEME_STORAGE_KEY,id)', $js);
        $this->assertStringContainsString('applySalesTheme(preferredTheme)', $js);
        $this->assertStringContainsString('salesThemeChoices()', $js);
        $this->assertStringContainsString('data-sales-theme-choice', $js);
        $this->assertStringContainsString('aria-pressed', $js);
        $this->assertStringContainsString('data-sales-theme="ocean"', $css);
        $this->assertStringContainsString('data-sales-theme="emerald"', $css);
        $this->assertStringContainsString('data-sales-theme="indigo"', $css);
        $this->assertStringContainsString('@media(max-width:420px)', $css);
        $layout = file_get_contents(resource_path('views/demo/sales-employees/layouts/app.blade.php'));
        $view = file_get_contents(resource_path('views/demo/sales-employees/index.blade.php'));
        $this->assertStringContainsString("filemtime(public_path('demo/sales-employees/css/app.css'))", $layout);
        $this->assertStringContainsString("filemtime(public_path('demo/sales-employees/js/app.js'))", $view);
    }
}
