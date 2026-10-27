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


    public function test_named_presets_can_be_saved_listed_and_loaded_without_changing_active_menu(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $url = '/demo/quan-ly-cong-no/menu/presets';
        $config = $this->menu();
        $config['header'] = ['title' => 'Công nợ', 'subtitle' => 'Theo dõi', 'icon' => 'wallet', 'href' => '#dashboard'];
        $config['footer'] = ['credit' => 'Demo', 'href' => 'https://example.com', 'showSave' => false];
        $this->postJson($url, ['name' => 'Mẫu số 1', 'config' => $config])->assertForbidden();
        $result = $this->postJson($url, ['name' => 'Mẫu số 1', 'config' => $config], [
            'X-Demo-Backup-Code' => 'this-is-a-long-private-code',
        ])->assertCreated()->json();
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'presets');
        $this->getJson($url.'/'.$result['id'])->assertOk()
            ->assertJsonPath('config.header.title', 'Công nợ')
            ->assertJsonPath('config.footer.showSave', false);
        $this->getJson('/demo/quan-ly-cong-no/menu')->assertJsonPath('config', null);
        $this->getJson($url.'/../menu')->assertStatus(404);
    }

    public function test_rejects_invalid_header_footer_and_unsafe_links(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $config = $this->menu();
        $config['header'] = ['title' => 'Demo', 'subtitle' => '', 'icon' => 'wallet', 'href' => 'javascript:alert(1)'];
        $this->postJson('/demo/quan-ly-cong-no/menu', ['config' => $config], [
            'X-Demo-Backup-Code' => 'this-is-a-long-private-code',
        ])->assertUnprocessable();
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
