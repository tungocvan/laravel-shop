<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class WarehouseDemoDatasetLibraryTest extends TestCase
{
    public function test_public_library_lists_and_loads_valid_json_without_authentication(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo-datasets/warehouse-inventory/dien-thoai-2026.json', json_encode([
            'ver' => 2,
            'products' => [['id' => 'p1', 'code' => 'SP001', 'name' => 'Điện thoại mẫu']],
            'txns' => [['pid' => 'p1', 'type' => 'in', 'qty' => 10]],
        ]));

        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets')
            ->assertOk()
            ->assertJsonPath('datasets.0.id', 'dien-thoai-2026')
            ->assertJsonPath('datasets.0.products', 1)
            ->assertJsonPath('datasets.0.txns', 1);

        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets/dien-thoai-2026')
            ->assertOk()
            ->assertJsonPath('products.0.name', 'Điện thoại mẫu');
    }

    public function test_invalid_json_is_not_listed_or_returned(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo-datasets/warehouse-inventory/broken.json', '{"products":');

        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets')
            ->assertOk()->assertJsonCount(0, 'datasets');
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets/broken')->assertNotFound();
    }

    public function test_path_traversal_and_writes_are_not_exposed(): void
    {
        Storage::fake('local');

        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets/invalid.json')->assertNotFound();
        $this->postJson('/demo/quan-ly-xuat-nhap-ton/datasets', ['products' => [], 'txns' => []])
            ->assertStatus(503);
    }

    public function test_server_backup_is_disabled_by_default_and_requires_correct_code(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $payload = $this->sampleBackupPayload();

        $this->postJson('/demo/quan-ly-xuat-nhap-ton/datasets', $payload)
            ->assertStatus(503);

        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');
        $this->withHeader('X-Demo-Backup-Code', 'wrong-code')
            ->postJson('/demo/quan-ly-xuat-nhap-ton/datasets', $payload)
            ->assertForbidden();

        $this->assertSame([], Storage::disk('local')->files('demo-datasets/warehouse-inventory'));
    }

    public function test_authorized_backup_creates_new_named_json_and_is_listed(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');

        $response = $this->withHeader('X-Demo-Backup-Code', 'correct-private-code-12345')
            ->postJson('/demo/quan-ly-xuat-nhap-ton/datasets', $this->sampleBackupPayload());

        $response->assertCreated()
            ->assertJsonPath('name', 'Kho điện thoại 2026')
            ->assertJsonPath('products', 1)
            ->assertJsonPath('txns', 1);

        $id = $response->json('id');
        $this->assertMatchesRegularExpression('/^[a-z0-9-]+$/', $id);
        Storage::disk('local')->assertExists('demo-datasets/warehouse-inventory/'.$id.'.json');

        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets')
            ->assertOk()->assertJsonPath('datasets.0.name', 'Kho điện thoại 2026');
        $this->getJson('/demo/quan-ly-xuat-nhap-ton/datasets/'.$id)
            ->assertOk()->assertJsonPath('products.0.name', 'Điện thoại mẫu');
    }

    public function test_invalid_backup_cannot_write_server_file(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'correct-private-code-12345');

        $payload = $this->sampleBackupPayload();
        $payload['data']['txns'][0]['pid'] = 'missing-product';

        $this->withHeader('X-Demo-Backup-Code', 'correct-private-code-12345')
            ->postJson('/demo/quan-ly-xuat-nhap-ton/datasets', $payload)
            ->assertUnprocessable();

        $this->assertSame([], Storage::disk('local')->files('demo-datasets/warehouse-inventory'));
    }

    private function sampleBackupPayload(): array
    {
        return [
            'name' => 'Kho điện thoại 2026',
            'data' => [
                'ver' => 2,
                'products' => [['id' => 'p1', 'code' => 'SP001', 'name' => 'Điện thoại mẫu']],
                'txns' => [['pid' => 'p1', 'type' => 'in', 'qty' => 10]],
            ],
        ];
    }

    public function test_settings_contains_dataset_selector_and_client_loader(): void
    {
        $this->get('/demo/quan-ly-xuat-nhap-ton')
            ->assertOk()
            ->assertSee('id="demoDataset"', false)
            ->assertSee('loadSelectedDemoDataset()', false);

        $script = file_get_contents(public_path('demo/warehouse-inventory/js/app.js'));
        $this->assertStringContainsString('async function renderDemoLibrary()', $script);
        $this->assertStringContainsString('if(!persist(next))', $script);
    }
}
