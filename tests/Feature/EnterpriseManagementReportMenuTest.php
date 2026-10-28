<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class EnterpriseManagementReportMenuTest extends TestCase
{
    private const URL = '/demo/bao-cao-quan-tri-doanh-nghiep/menu';
    private const SECRET = 'long-private-demo-operator-code';

    private function menu(): array
    {
        return [
            'version' => 1,
            'header' => ['title' => 'Báo cáo quản trị', 'subtitle' => '', 'icon' => 'chart', 'href' => '#dashboard'],
            'footer' => ['credit' => '', 'href' => '', 'showSave' => false],
            'groups' => [
                ['title' => 'Tổng quan', 'items' => [
                    ['id' => 'dashboard', 'label' => 'Dashboard', 'icon' => 'dash', 'href' => '#dashboard'],
                    ['id' => 'external_link', 'label' => 'Website', 'icon' => 'list', 'href' => 'https://example.com'],
                ]],
            ],
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', self::SECRET);
        // CRUD assertions are independent of the API rate-limit contract.
        // Throttle is verified separately in its own test.
        $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);
    }

    private function auth(): array
    {
        return ['X-Demo-Backup-Code' => self::SECRET];
    }

    public function test_read_only_menu_and_protected_write(): void
    {
        $this->getJson(self::URL)->assertOk()->assertJsonPath('config', null);
        $this->postJson(self::URL, ['config' => $this->menu()])->assertForbidden();
        $this->postJson(self::URL, ['config' => $this->menu()], $this->auth())->assertOk()->assertJsonPath('ok', true);
        $this->getJson(self::URL)->assertJsonPath('config.footer.credit', '')
            ->assertJsonPath('config.header.subtitle', '');
        Storage::disk('local')->assertExists('demo-settings/enterprise-management-report/sidebar-menu.json');
    }

    public function test_unconfigured_code_disables_writes(): void
    {
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson(self::URL, ['config' => $this->menu()], $this->auth())->assertStatus(503);
    }

    public function test_rejects_unsafe_urls_duplicate_ids_and_invalid_icons(): void
    {
        $bad = $this->menu();
        $bad['groups'][0]['items'][1]['href'] = 'javascript:alert(1)';
        $this->postJson(self::URL, ['config' => $bad], $this->auth())->assertUnprocessable();

        $bad = $this->menu();
        $bad['groups'][0]['items'][1]['id'] = 'dashboard';
        $this->postJson(self::URL, ['config' => $bad], $this->auth())->assertUnprocessable();

        $bad = $this->menu();
        $bad['groups'][0]['items'][0]['icon'] = '<svg>';
        $this->postJson(self::URL, ['config' => $bad], $this->auth())->assertUnprocessable();
    }

    public function test_presets_conflict_update_and_delete_do_not_change_active_menu(): void
    {
        $this->postJson(self::URL, ['config' => $this->menu()], $this->auth())->assertOk();
        $payload = ['name' => 'Menu kinh doanh', 'config' => $this->menu()];
        $this->postJson(self::URL.'/presets', $payload)->assertForbidden();
        $created = $this->postJson(self::URL.'/presets', $payload, $this->auth())->assertCreated()->json();
        $id = $created['id'];
        $this->postJson(self::URL.'/presets', $payload, $this->auth())->assertStatus(409);
        $this->getJson(self::URL.'/presets')->assertJsonCount(1, 'presets');
        $this->getJson(self::URL.'/presets/'.$id)->assertJsonPath('config.header.title', 'Báo cáo quản trị');

        $changed = $this->menu();
        $changed['header']['title'] = 'Menu đã chỉnh sửa';
        $this->putJson(self::URL.'/presets/'.$id, ['config' => $changed])->assertForbidden();
        $this->putJson(self::URL.'/presets/'.$id, ['config' => $changed], $this->auth())->assertOk()->assertJsonPath('updated', true);
        $this->getJson(self::URL.'/presets/'.$id)->assertJsonPath('config.header.title', 'Menu đã chỉnh sửa');
        $this->getJson(self::URL)->assertJsonPath('config.header.title', 'Báo cáo quản trị');
        $this->deleteJson(self::URL.'/presets/'.$id)->assertForbidden();
        $this->deleteJson(self::URL.'/presets/'.$id, [], $this->auth())->assertOk();
        $this->getJson(self::URL.'/presets')->assertJsonCount(0, 'presets');
        $this->getJson(self::URL)->assertJsonPath('config.header.title', 'Báo cáo quản trị');
    }

    public function test_menu_write_endpoints_remain_rate_limited(): void
    {
        // Restore the actual throttle middleware for this security contract.
        $this->withMiddleware();
        for ($i = 0; $i < 5; $i++) {
            $this->postJson(self::URL, ['config' => $this->menu()], $this->auth())->assertOk();
        }
        $this->postJson(self::URL, ['config' => $this->menu()], $this->auth())->assertStatus(429);
    }

    public function test_invalid_preset_path_cannot_escape_private_folder(): void
    {
        $this->getJson(self::URL.'/presets/not-found')->assertNotFound();
        $this->deleteJson(self::URL.'/presets/not-found', [], $this->auth())->assertNotFound();
    }
}
