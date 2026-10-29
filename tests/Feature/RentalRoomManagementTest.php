<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class RentalRoomManagementTest extends TestCase
{
    public function test_demo_renders_original_screens_and_separate_assets(): void
    {
        $response = $this->get('/demo/quan-ly-phong-tro');
        $response->assertOk()
            ->assertSee('Quản Lý Phòng Trọ')
            ->assertSee('page-dashboard')
            ->assertSee('page-rooms')
            ->assertSee('page-meter')
            ->assertSee('page-invoices')
            ->assertSee('page-debts')
            ->assertSee('page-tenants')
            ->assertSee('page-report')
            ->assertSee('page-settings')
            ->assertSee('page-guide')
            ->assertSee('demo-assets/quan-ly-phong-tro/js/app.js')
            ->assertSee('demo-assets/quan-ly-phong-tro/js/integrations.js');
        foreach (['css/app.css','js/app.js','js/integrations.js'] as $asset) {
            $this->assertFileExists(public_path('demo-assets/quan-ly-phong-tro/'.$asset));
        }
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-phong-tro'));
    }

    public function test_sidebar_menu_uses_isolated_namespace(): void
    {
        $this->get('/demo/quan-ly-phong-tro/menu')->assertOk()->assertJsonStructure(['config']);
        $this->get('/demo/quan-ly-phong-tro/menu/presets')->assertOk()->assertJsonStructure(['presets']);
    }

    public function test_private_datasets_require_operator_secret(): void
    {
        config()->set('warehouse_demo.backup_code', str_repeat('x', 20));
        $this->get('/demo/quan-ly-phong-tro/datasets')->assertForbidden();
        $this->postJson('/demo/quan-ly-phong-tro/datasets', ['name'=>'sample','data'=>[]])->assertForbidden();
    }

    public function test_valid_private_snapshot_can_be_saved_and_read(): void
    {
        Storage::fake('local');
        $secret = str_repeat('x', 20);
        config()->set('warehouse_demo.backup_code', $secret);
        $data = ['rooms'=>[], 'contracts'=>[], 'readings'=>[], 'invoices'=>[], 'payments'=>[], 'cfg'=>[]];
        $saved = $this->withHeader('X-Demo-Backup-Code', $secret)
            ->postJson('/demo/quan-ly-phong-tro/datasets', ['name'=>'Mẫu thử','data'=>$data]);
        $saved->assertCreated()->assertJsonStructure(['id','name']);
        $id = $saved->json('id');
        $this->withHeader('X-Demo-Backup-Code', $secret)
            ->get('/demo/quan-ly-phong-tro/datasets/'.$id)
            ->assertOk()->assertJsonPath('data.rooms', []);
    }
}
