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
            $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/rental-room/'.$asset));
            $this->assertFileDoesNotExist(public_path('demo-assets/quan-ly-phong-tro/'.$asset));
            $response = $this->get('/demo-assets/quan-ly-phong-tro/'.$asset);
            $response->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff')
                ->assertHeader('Cache-Control', function (string $value): bool {
                    $directives = array_map('trim', explode(',', $value));

                    return count($directives) === 3
                        && count(array_unique($directives)) === 3
                        && count(array_intersect($directives, ['public', 'max-age=300', 'must-revalidate'])) === 3;
                });
            $this->assertSame(
                realpath(base_path('Modules/Demo/resources/assets/projects/rental-room/'.$asset)),
                realpath($response->baseResponse->getFile()->getPathname())
            );
        }
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-phong-tro'));
    }

    public function test_asset_route_rejects_unknown_files_and_traversal(): void
    {
        $this->get('/demo-assets/quan-ly-phong-tro/js/unknown.js')->assertNotFound();
        $this->get('/demo-assets/quan-ly-phong-tro/css/other.css')->assertNotFound();
        $this->get('/demo-assets/quan-ly-phong-tro/config.php')->assertNotFound();
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
