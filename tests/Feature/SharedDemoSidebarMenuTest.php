<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SharedDemoSidebarMenuTest extends TestCase
{
    private function menu(): array
    {
        return [
            'version' => 1,
            'header' => ['title' => 'Demo', 'subtitle' => 'Settings', 'icon' => 'menu', 'href' => '#dashboard'],
            'footer' => ['credit' => 'Demo', 'href' => '', 'showSave' => false],
            'groups' => [['title' => 'Tổng quan', 'items' => [
                ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'home', 'href' => '#dashboard'],
            ]]],
        ];
    }

    public function test_shared_assets_are_mounted_in_both_demo_views(): void
    {
        foreach (['doanh-so-nhan-vien', 'quan-ly-xuat-nhap-ton'] as $demo) {
            $this->get('/demo/'.$demo)->assertOk()
                ->assertSee('data-demo-menu="'.$demo.'"', false)
                ->assertSee('demo/shared/sidebar-menu/menu-builder.js');
        }
    }

    public function test_each_demo_has_independent_menu_storage(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('A', 20));
        $menu = $this->menu();
        $response = $this->postJson('/demo/doanh-so-nhan-vien/menu', ['config' => $menu], ['X-Demo-Backup-Code' => str_repeat('A', 20)]);
        $this->assertSame(200, $response->status(), 'Menu response: '.$response->getContent());
        $this->getJson('/demo/doanh-so-nhan-vien/menu')->assertOk()->assertJsonPath('config.header.title', 'Demo');
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/menu')->assertOk()->assertJsonPath('config', null);
        Storage::disk('local')->assertExists('demo-settings/sales-employees/sidebar-menu.json');
        Storage::disk('local')->assertMissing('demo-settings/warehouse-inventory/sidebar-menu.json');
    }

    public function test_invalid_or_unauthorized_writes_are_rejected(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('B', 20));
        $url = '/demo/quan-ly-xuat-nhap-ton/menu';
        $this->postJson($url, ['config' => $this->menu()])->assertForbidden();
        $bad = $this->menu();
        $bad['groups'][0]['items'][0]['href'] = 'javascript:alert(1)';
        $this->postJson($url, ['config' => $bad], ['X-Demo-Backup-Code' => str_repeat('B', 20)])->assertStatus(422);
        Storage::disk('local')->assertMissing('demo-settings/warehouse-inventory/sidebar-menu.json');
    }

    public function test_presets_are_isolated_and_reject_path_traversal(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', str_repeat('C', 20));
        $response = $this->postJson('/demo/doanh-so-nhan-vien/menu/presets', [
            'name' => 'Menu A', 'config' => $this->menu(),
        ], ['X-Demo-Backup-Code' => str_repeat('C', 20)]);
        $this->assertSame(201, $response->status(), 'Preset response: '.$response->getContent());
        $this->getJson('/demo/doanh-so-nhan-vien/menu/presets')->assertOk()->assertJsonCount(1, 'presets');
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/menu/presets')->assertOk()->assertJsonCount(0, 'presets');
        $this->getJson('/demo/doanh-so-nhan-vien/menu/presets/%2E%2E')->assertNotFound();
    }
}
