<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DailyWorkReportMenuTest extends TestCase
{
    private function menu(): array
    {
        return [
            'version' => 1,
            'header' => ['title' => 'Báo cáo công việc', 'subtitle' => 'Hằng ngày', 'icon' => 'menu', 'href' => '#dashboard'],
            'footer' => ['credit' => 'Công ty', 'href' => '', 'showSave' => false],
            'groups' => [['title' => 'Tổng quan', 'items' => [
                ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dash', 'href' => '#dashboard'],
            ]]],
        ];
    }

    public function test_demo_mounts_shared_editor_and_menu_routes(): void
    {
        $this->get('/demo/bao-cao-cong-viec-hang-ngay')->assertOk()
            ->assertSee('data-demo-menu="bao-cao-cong-viec-hang-ngay"', false)
            ->assertSee('demo/shared/sidebar-menu/menu-builder.js')
            ->assertSee('name="csrf-token"', false);

        $this->getJson('/demo/bao-cao-cong-viec-hang-ngay/menu')
            ->assertOk()->assertJsonStructure(['config']);
    }

    public function test_settings_contains_visible_menu_entry_and_theme_cards(): void
    {
        $script = file_get_contents(public_path('demo-assets/bao-cao-cong-viec-hang-ngay/js/app.js'));
        $css = file_get_contents(public_path('demo-assets/bao-cao-cong-viec-hang-ngay/css/app.css'));
        $this->assertStringContainsString('data-open-menu>Chỉnh sửa menu', $script);
        $this->assertStringContainsString('daily-theme-swatch--', $script);
        $this->assertStringContainsString('grid-template-columns:repeat(4,minmax(0,1fr))', $css);
    }

    public function test_menu_is_isolated_and_requires_operator_code(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('D', 20));
        $url = '/demo/bao-cao-cong-viec-hang-ngay/menu';

        $this->postJson($url, ['config' => $this->menu()])->assertForbidden();

        $invalid = $this->menu();
        $invalid['groups'][0]['items'][0]['href'] = 'javascript:alert(1)';
        $this->postJson($url, ['config' => $invalid], ['X-Demo-Backup-Code' => str_repeat('D', 20)])
            ->assertStatus(422);

        $this->postJson($url, ['config' => $this->menu()], ['X-Demo-Backup-Code' => str_repeat('D', 20)])
            ->assertOk();
        Storage::disk('local')->assertExists('demo-settings/daily-work-report/sidebar-menu.json');
        Storage::disk('local')->assertMissing('demo-settings/sales-employees/sidebar-menu.json');
        $this->getJson($url)->assertOk()->assertJsonPath('config.header.title', 'Báo cáo công việc');
    }

    public function test_presets_are_isolated_from_other_demos(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('E', 20));
        $url = '/demo/bao-cao-cong-viec-hang-ngay/menu/presets';
        $this->postJson($url, ['name' => 'Menu mẫu', 'config' => $this->menu()], [
            'X-Demo-Backup-Code' => str_repeat('E', 20),
        ])->assertCreated();
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'presets');
        $this->getJson('/demo/doanh-so-nhan-vien/menu/presets')->assertOk()->assertJsonCount(0, 'presets');
    }
}
