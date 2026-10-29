<?php
namespace Tests\Feature;
use Tests\TestCase;
class CoffeeShopSalesDemoTest extends TestCase
{
    public function test_demo_renders_and_assets_are_versioned(): void
    {
        $this->get('/demo/quan-ly-ban-hang-ca-phe')->assertOk()
            ->assertSee('demo-assets/quan-ly-ban-hang-ca-phe/css/app.css?v=', false)
            ->assertSee('demo-assets/quan-ly-ban-hang-ca-phe/js/app.js?v=', false)
            ->assertSee('du-lieu', false);
    }
    public function test_integrations_are_mounted(): void
    {
        $this->get('/demo/quan-ly-ban-hang-ca-phe')->assertOk()
            ->assertSee('data-demo-menu="quan-ly-ban-hang-ca-phe"', false)
            ->assertSee('js/integrations.js?v=', false)
            ->assertSee('csrf-token', false);
    }

    public function test_dataset_library_starts_empty(): void
    {
        config()->set('warehouse_demo.backup_code', 'coffee-test-secret-12345');
        \Illuminate\Support\Facades\Storage::fake('local');
        $this->withHeader('X-Demo-Backup-Code', 'coffee-test-secret-12345')
            ->getJson('/demo/quan-ly-ban-hang-ca-phe/datasets')
            ->assertOk()->assertExactJson(['datasets' => []]);
    }

    public function test_dataset_write_is_protected_when_secret_is_missing(): void
    {
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/quan-ly-ban-hang-ca-phe/datasets', [
            'name' => 'Dữ liệu quán', 'data' => ['nl' => [], 'menu' => [], 'orders' => []],
        ])->assertStatus(503);
    }

    public function test_dataset_roundtrip_with_valid_secret(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'coffee-test-secret-12345');
        $payload = ['name' => 'Bản thử nghiệm', 'data' => [
            'nl' => [], 'menu' => [], 'orders' => [], 'ver' => 1,
        ]];
        $result = $this->withHeader('X-Demo-Backup-Code', 'coffee-test-secret-12345')
            ->postJson('/demo/quan-ly-ban-hang-ca-phe/datasets', $payload)
            ->assertCreated()->assertJsonPath('name', 'Bản thử nghiệm');
        $id = $result->json('id');
        $this->getJson('/demo/quan-ly-ban-hang-ca-phe/datasets/'.$id)
            ->assertOk()->assertJsonPath('data.ver', 1);
    }

    public function test_dataset_reads_require_valid_backup_code(): void
    {
        \Illuminate\Support\Facades\Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'coffee-test-secret-12345');
        $url = '/demo/quan-ly-ban-hang-ca-phe/datasets';
        $this->getJson($url)->assertForbidden();
        $this->getJson($url.'/example')->assertForbidden();
        $this->withHeader('X-Demo-Backup-Code', 'wrong-code')
            ->getJson($url)->assertForbidden();
    }

    public function test_dataset_reads_are_disabled_without_server_secret(): void
    {
        config()->set('warehouse_demo.backup_code', null);
        $this->getJson('/demo/quan-ly-ban-hang-ca-phe/datasets')->assertStatus(503);
    }

    public function test_assets_do_not_shadow_route(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-ban-hang-ca-phe'));
        $this->assertFileExists(public_path('demo-assets/quan-ly-ban-hang-ca-phe/js/app.js'));
    }
}
