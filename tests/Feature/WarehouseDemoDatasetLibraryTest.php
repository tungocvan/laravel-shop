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
            ->assertStatus(405);
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
