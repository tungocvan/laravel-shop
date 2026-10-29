<?php
namespace Tests\Feature;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
final class ProductCostingDemoTest extends TestCase
{
    public function test_demo_renders_with_separate_assets_and_shared_menu(): void
    {
        $this->get('/demo/tinh-gia-thanh-san-pham')->assertOk()
            ->assertSee('demo-assets/tinh-gia-thanh-san-pham/css/app.css?v=',false)
            ->assertSee('demo-assets/tinh-gia-thanh-san-pham/js/app.js?v=',false)
            ->assertSee('data-demo-menu="tinh-gia-thanh-san-pham"',false)
            ->assertSee('id="du-lieu"',false);
    }
    public function test_dataset_reads_and_writes_require_secret(): void
    {
        config()->set('warehouse_demo.backup_code','product-cost-test-secret-123');
        $this->getJson('/demo/tinh-gia-thanh-san-pham/datasets')->assertForbidden();
        $this->postJson('/demo/tinh-gia-thanh-san-pham/datasets',['name'=>'Bản thử','data'=>[]])->assertForbidden();
    }
    public function test_dataset_is_disabled_when_secret_is_not_configured(): void
    {
        config()->set('warehouse_demo.backup_code',null);
        $this->getJson('/demo/tinh-gia-thanh-san-pham/datasets')->assertStatus(503);
    }
    public function test_dataset_roundtrip(): void
    {
        Storage::fake('local');config()->set('warehouse_demo.backup_code','product-cost-test-secret-123');
        $base='/demo/tinh-gia-thanh-san-pham/datasets';
        $payload=['name'=>'Tháng 10','data'=>['products'=>[],'mats'=>[],'periods'=>[],'version'=>1]];
        $result=$this->withHeader('X-Demo-Backup-Code','product-cost-test-secret-123')
            ->postJson($base,$payload)->assertCreated();
        $id=$result->json('id');
        $this->getJson($base.'/'.$id)->assertOk()->assertJsonPath('data.version',1);
        $this->getJson($base)->assertOk()->assertJsonCount(1,'datasets');
    }
    public function test_invalid_dataset_is_rejected(): void
    {
        Storage::fake('local');config()->set('warehouse_demo.backup_code','product-cost-test-secret-123');
        $this->withHeader('X-Demo-Backup-Code','product-cost-test-secret-123')
            ->postJson('/demo/tinh-gia-thanh-san-pham/datasets',['name'=>'Không hợp lệ','data'=>['products'=>[]]])->assertUnprocessable();
    }
}
