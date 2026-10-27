<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DebtManagementMenuTest extends TestCase
{
    private function menu(): array
    {
        return [
            'version' => 1,
            'groups' => [
                ['title' => 'Tổng quan', 'items' => [
                    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'layout-dashboard', 'href' => '#dashboard'],
                    ['id' => 'custom-one', 'label' => 'Website', 'icon' => 'external-link', 'href' => 'https://example.com'],
                ]],
            ],
        ];
    }

    public function test_menu_is_read_only_public_and_private_on_disk(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/quan-ly-cong-no/menu')->assertOk()->assertJsonPath('config', null);
        Storage::disk('local')->put('demo-settings/debt-management/sidebar-menu.json', json_encode($this->menu()));
        $this->getJson('/demo/quan-ly-cong-no/menu')->assertOk()
            ->assertJsonPath('config.groups.0.items.0.href', '#dashboard');
    }

    public function test_write_requires_configured_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/quan-ly-cong-no/menu', ['config' => $this->menu()])->assertStatus(503);
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $this->postJson('/demo/quan-ly-cong-no/menu', ['config' => $this->menu()])->assertForbidden();
    }

    public function test_valid_write_and_invalid_links_or_duplicate_ids(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $headers = ['X-Demo-Backup-Code' => 'this-is-a-long-private-code'];
        $url = '/demo/quan-ly-cong-no/menu';

        $bad = $this->menu();
        $bad['groups'][0]['items'][1]['href'] = 'javascript:alert(1)';
        $this->postJson($url, ['config' => $bad], $headers)->assertUnprocessable();

        $bad = $this->menu();
        $bad['groups'][0]['items'][1]['id'] = 'dashboard';
        $this->postJson($url, ['config' => $bad], $headers)->assertUnprocessable();

        $this->postJson($url, ['config' => $this->menu()], $headers)->assertOk()
            ->assertJsonPath('saved', true);
        Storage::disk('local')->assertExists('demo-settings/debt-management/sidebar-menu.json');
        $this->getJson($url)->assertJsonPath('config.groups.0.items.1.label', 'Website');
    }

    public function test_rejects_oversized_menu(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $menu = $this->menu();
        $menu['groups'][0]['items'][0]['label'] = str_repeat('a', 66000);
        $this->postJson('/demo/quan-ly-cong-no/menu', ['config' => $menu], [
            'X-Demo-Backup-Code' => 'this-is-a-long-private-code',
        ])->assertStatus(413);
    }
}
