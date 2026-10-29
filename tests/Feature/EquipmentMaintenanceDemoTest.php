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

    public function test_shared_menu_endpoints_are_registered(): void
    {
        $this->get('/demo/quan-ly-thiet-bi-bao-tri/menu')->assertOk()->assertJsonStructure(['config']);
        $this->get('/demo/quan-ly-thiet-bi-bao-tri/menu/presets')->assertOk()->assertJsonStructure(['presets']);
    }


    public function test_json_menu_preset_can_be_selected_as_server_default(): void
    {
        Storage::fake('local');
        config()->set('warehouse_demo.backup_code', 'testing-equipment-secret-123');
        $config=[
            'version'=>1,
            'header'=>['title'=>'Thiết bị','subtitle'=>'Bảo trì','icon'=>'menu','href'=>'#dashboard'],
            'footer'=>['credit'=>'','href'=>'','showSave'=>false],
            'groups'=>[['title'=>'Tổng quan','items'=>[['id'=>'dashboard','label'=>'Dashboard','icon'=>'link','href'=>'#dashboard']]]],
        ];
        $path='demo-settings/equipment-maintenance/presets/sample-menu.json';
        Storage::disk('local')->put($path, json_encode(['name'=>'Sample menu','config'=>$config]));
        $url='/demo/quan-ly-thiet-bi-bao-tri/menu';
        $this->putJson($url.'/presets/sample-menu/default')->assertForbidden();
        $this->putJson($url.'/presets/sample-menu/default', [], ['X-Demo-Backup-Code'=>'testing-equipment-secret-123'])->assertOk()->assertJsonPath('defaultId','sample-menu');
        $this->getJson($url.'/presets')->assertOk()->assertJsonPath('defaultId','sample-menu');
        $this->getJson($url)->assertOk()->assertJsonPath('config.header.title','Thiết bị');
        $this->deleteJson($url.'/presets/sample-menu', [], ['X-Demo-Backup-Code'=>'testing-equipment-secret-123'])->assertStatus(409);
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
