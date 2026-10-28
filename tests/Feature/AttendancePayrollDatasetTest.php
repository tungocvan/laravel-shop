<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class AttendancePayrollDatasetTest extends TestCase
{
    private function data(): array
    {
        return ['settings' => ['company' => ['ten' => 'Test']], 'employees' => [
            ['id' => 'e1', 'ma' => 'NV001', 'ten' => 'Nhân viên mẫu'],
        ], 'cham' => [], 'extra' => []];
    }

    public function test_public_list_and_read_are_read_only(): void
    {
        Storage::fake('local');
        $this->getJson('/demo/cham-cong-tinh-luong/datasets')->assertOk()->assertJson(['datasets' => []]);
        $this->getJson('/demo/cham-cong-tinh-luong/datasets/unknown')->assertNotFound();
        $this->getJson('/demo/cham-cong-tinh-luong/datasets/..%2Fsecret')->assertNotFound();
    }

    public function test_server_write_requires_configured_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', null);
        $this->postJson('/demo/cham-cong-tinh-luong/datasets', ['name' => 'Test', 'data' => $this->data()])->assertStatus(503);
    }

    public function test_server_write_rejects_invalid_secret(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'secret-for-testing-only');
        $this->withHeader('X-Demo-Backup-Code', 'incorrect')->postJson('/demo/cham-cong-tinh-luong/datasets', ['name' => 'Test', 'data' => $this->data()])->assertForbidden();
    }

    public function test_authorized_write_and_read(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'secret-for-testing-only');
        $response = $this->withHeader('X-Demo-Backup-Code', 'secret-for-testing-only')
            ->postJson('/demo/cham-cong-tinh-luong/datasets', ['name' => 'Bảng lương mẫu', 'data' => $this->data()]);
        $response->assertCreated()->assertJsonStructure(['id', 'name']);
        $id = $response->json('id');
        $this->getJson('/demo/cham-cong-tinh-luong/datasets/'.$id)->assertOk()->assertJsonPath('data.employees.0.id', 'e1');
        $this->getJson('/demo/cham-cong-tinh-luong/datasets')->assertOk()->assertJsonCount(1, 'datasets');
    }

    public function test_authorized_write_rejects_invalid_data(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'secret-for-testing-only');
        $this->withHeader('X-Demo-Backup-Code', 'secret-for-testing-only')
            ->postJson('/demo/cham-cong-tinh-luong/datasets', ['name' => 'Test', 'data' => ['employees' => []]])
            ->assertUnprocessable();
    }
}
