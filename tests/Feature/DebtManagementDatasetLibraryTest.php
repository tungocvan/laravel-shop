<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class DebtManagementDatasetLibraryTest extends TestCase
{
    private function sample(): array
    {
        return [
            '_app' => 'congno',
            'settings' => ['company' => ['ten' => 'Demo']],
            'partners' => [['id' => 'p1', 'ten' => 'Đối tác mẫu']],
            'vouchers' => [['id' => 'v1', 'partnerId' => 'p1', 'loai' => 'PS']],
        ];
    }

    public function test_public_library_lists_and_loads_valid_data(): void
    {
        Storage::fake('local');
        Storage::disk('local')->put('demo-datasets/debt-management/mau.json', json_encode($this->sample()));
        Storage::disk('local')->put('demo-datasets/debt-management/bad.json', '{broken');

        $this->getJson('/demo/quan-ly-cong-no/datasets')->assertOk()
            ->assertJsonCount(1, 'datasets')->assertJsonPath('datasets.0.id', 'mau');
        $this->getJson('/demo/quan-ly-cong-no/datasets/mau')->assertOk()
            ->assertJsonPath('partners.0.id', 'p1');
        $this->getJson('/demo/quan-ly-cong-no/datasets/bad')->assertNotFound();
        $this->getJson('/demo/quan-ly-cong-no/datasets/%2E%2E')->assertStatus(404);
    }

    public function test_server_writes_require_configured_operator_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/quan-ly-cong-no/datasets', ['name' => 'Test', 'data' => $this->sample()])
            ->assertStatus(503);

        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $this->postJson('/demo/quan-ly-cong-no/datasets', ['name' => 'Test', 'data' => $this->sample()])
            ->assertForbidden();
    }

    public function test_valid_operator_can_create_without_overwriting(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        $url = '/demo/quan-ly-cong-no/datasets';
        $headers = ['X-Demo-Backup-Code' => 'this-is-a-long-private-code'];

        $this->postJson($url, ['name' => 'Sai dữ liệu', 'data' => [
            'settings' => [], 'partners' => [], 'vouchers' => [['id' => 'v1', 'partnerId' => 'missing']],
        ]], $headers)->assertUnprocessable();

        $this->postJson($url, ['name' => 'Công nợ mẫu', 'data' => $this->sample()], $headers)
            ->assertCreated()->assertJsonStructure(['id', 'name']);
        $this->getJson($url)->assertOk()->assertJsonCount(1, 'datasets');
    }

    public function test_size_and_file_count_limits(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'this-is-a-long-private-code');
        config()->set('warehouse_demo.max_bytes', 100);
        $headers = ['X-Demo-Backup-Code' => 'this-is-a-long-private-code'];
        $url = '/demo/quan-ly-cong-no/datasets';
        $this->postJson($url, ['name' => 'Công nợ mẫu', 'data' => $this->sample()], $headers)
            ->assertStatus(413);

        config()->set('warehouse_demo.max_bytes', 10485760);
        config()->set('warehouse_demo.max_datasets', 0);
        $this->postJson($url, ['name' => 'Công nợ mẫu', 'data' => $this->sample()], $headers)
            ->assertStatus(409);
    }
}
