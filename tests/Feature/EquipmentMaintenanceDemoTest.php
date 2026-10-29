<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class EquipmentMaintenanceDemoTest extends TestCase
{
    public function test_demo_renders_with_assets_and_shared_menu(): void
    {
        $this->get('/demo/quan-ly-thiet-bi-bao-tri')->assertOk()
            ->assertSee('demo-assets/quan-ly-thiet-bi-bao-tri/js/app.js', false)
            ->assertSee('demo-assets/quan-ly-thiet-bi-bao-tri/js/integrations.js', false)
            ->assertSee('data-demo-menu="quan-ly-thiet-bi-bao-tri"', false)
            ->assertSee('id="sb"', false);
    }

    public function test_assets_contain_business_logic_and_five_themes(): void
    {
        $js=file_get_contents(public_path('demo-assets/quan-ly-thiet-bi-bao-tri/js/app.js'));
        $integration=file_get_contents(public_path('demo-assets/quan-ly-thiet-bi-bao-tri/js/integrations.js'));
        $this->assertStringContainsString('function buildShell()', $js);
        $this->assertStringContainsString('function renderSettings()', $js);
        foreach (['original','ruby','ocean','emerald','indigo'] as $theme) {
            $this->assertStringContainsString($theme.':', $integration);
        }
    }

    public function test_snapshot_routes_require_backup_code(): void
    {
        config()->set('warehouse_demo.backup_code', 'testing-equipment-secret-123');
        $this->get('/demo/quan-ly-thiet-bi-bao-tri/datasets')->assertForbidden();
        $this->postJson('/demo/quan-ly-thiet-bi-bao-tri/datasets', ['name'=>'Test','data'=>[]])->assertForbidden();
    }

    public function test_snapshot_storage_and_restore(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'testing-equipment-secret-123');
        $headers=['X-Demo-Backup-Code'=>'testing-equipment-secret-123'];
        $data=['tb'=>[],'plans'=>[],'logs'=>[],'ncc'=>[],'stlog'=>[]];
        $response=$this->postJson('/demo/quan-ly-thiet-bi-bao-tri/datasets', ['name'=>'Bản thử nghiệm','data'=>$data], $headers)->assertCreated();
        $id=$response->json('id');
        $this->assertNotEmpty($id);
        $this->withHeaders($headers)->getJson('/demo/quan-ly-thiet-bi-bao-tri/datasets')->assertOk()->assertJsonCount(1,'datasets');
        $this->withHeaders($headers)->getJson('/demo/quan-ly-thiet-bi-bao-tri/datasets/'.$id)->assertOk()->assertJsonPath('data.tb',[]);
    }

    public function test_snapshot_rejects_invalid_state(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'testing-equipment-secret-123');
        $this->postJson('/demo/quan-ly-thiet-bi-bao-tri/datasets', ['name'=>'Bad','data'=>['tb'=>[]]], ['X-Demo-Backup-Code'=>'testing-equipment-secret-123'])->assertUnprocessable();
    }
}
