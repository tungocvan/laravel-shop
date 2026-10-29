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
            ->assertSee('demo-assets/quan-ly-phong-tro/js/integrations.js')
            ->assertSee('demo-assets/quan-ly-phong-tro/shared/menu-editor.js')
            ->assertSee('demo-assets/quan-ly-phong-tro/shared/menu-editor.css')
            ->assertSee('demo-assets/quan-ly-phong-tro/shared/theme-selector.js')
            ->assertSee('data-demo-theme=');
        foreach (['css/app.css','js/app.js','js/integrations.js'] as $asset) {
            $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/rental-room/'.$asset));
            $this->assertFileDoesNotExist(public_path('demo-assets/quan-ly-phong-tro/'.$asset));
            $response = $this->get('/demo-assets/quan-ly-phong-tro/'.$asset);
            $response->assertOk()
                ->assertHeader('X-Content-Type-Options', 'nosniff');
            $cacheControl = $response->headers->get('Cache-Control');
            $this->assertNotNull($cacheControl);
            $directives = array_map('trim', explode(',', $cacheControl));
            $this->assertCount(3, $directives);
            $this->assertCount(3, array_unique($directives));
            foreach (['public', 'max-age=300', 'must-revalidate'] as $directive) {
                $this->assertContains($directive, $directives);
            }
            $this->assertSame(
                realpath(base_path('Modules/Demo/resources/assets/projects/rental-room/'.$asset)),
                realpath($response->baseResponse->getFile()->getPathname())
            );
        }
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-phong-tro'));
    }

    public function test_shared_editor_assets_are_served_from_demo_module(): void
    {
        foreach (['menu-editor.css', 'menu-editor.js', 'theme-selector.js'] as $asset) {
            $path = base_path('Modules/Demo/resources/assets/shared/'.$asset);
            $this->assertFileExists($path);
            $response = $this->get('/demo-assets/quan-ly-phong-tro/shared/'.$asset);
            $response->assertOk()->assertHeader('X-Content-Type-Options', 'nosniff');
            $this->assertSame(realpath($path), realpath($response->baseResponse->getFile()->getPathname()));
        }
        $this->assertStringNotContainsString('const menuPanel=', file_get_contents(base_path('Modules/Demo/resources/assets/projects/rental-room/js/integrations.js')));
        $this->assertStringContainsString('Thư viện JSON server', file_get_contents(base_path('Modules/Demo/resources/assets/shared/menu-editor.js')));
    }

    public function test_rental_room_uses_module_shared_layout_and_sidebar_contract(): void
    {
        $index = file_get_contents(base_path('Modules/Demo/resources/views/projects/rental-room/partials/sidebar.blade.php'));
        $layout = file_get_contents(base_path('Modules/Demo/resources/views/projects/rental-room/layouts/app.blade.php'));
        $theme = file_get_contents(base_path('Modules/Demo/resources/assets/shared/theme-selector.js'));
        $integration = file_get_contents(base_path('Modules/Demo/resources/assets/projects/rental-room/js/integrations.js'));
        $this->assertStringContainsString("demo::components.sidebar", $index);
        $this->assertStringContainsString("demo::layouts.app", $layout);
        $this->assertStringContainsString("rental_room_theme_v1", $layout);
        $this->assertStringContainsString("const palettes=", $theme);
        $this->assertStringNotContainsString("const palettes=", $integration);
        $this->assertStringContainsString('id="bEmpty"', file_get_contents(base_path('Modules/Demo/resources/views/projects/rental-room/partials/sidebar-nav.blade.php')));
        $this->assertStringContainsString('id="sbValue"', file_get_contents(base_path('Modules/Demo/resources/views/projects/rental-room/partials/sidebar-card.blade.php')));
    }

    public function test_dataset_integration_defines_its_own_form_and_api_helpers(): void
    {
        $source = file_get_contents(base_path('Modules/Demo/resources/assets/projects/rental-room/js/integrations.js'));
        $this->assertStringContainsString('const input=', $source);
        $this->assertStringContainsString('async function api(', $source);
        $this->assertStringContainsString("'X-Demo-Backup-Code':code", $source);
        $this->assertStringContainsString("'X-CSRF-TOKEN':csrf", $source);
        $this->assertStringContainsString('if(result)notify(', $source);
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
