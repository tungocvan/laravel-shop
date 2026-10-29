<?php

namespace Tests\Feature;

use Tests\TestCase;

final class DemoSharedSidebarAssetOwnershipTest extends TestCase
{
    public function test_shared_editor_assets_are_served_from_demo_module(): void
    {
        foreach ([
            'menu-builder.js' => 'text/javascript',
            'menu-builder.css' => 'text/css',
        ] as $asset => $mime) {
            $response = $this->get('/demo-assets/shared/sidebar-menu/'.$asset)->assertOk();
            $this->assertStringContainsString($mime, (string) $response->headers->get('Content-Type'));
            $this->assertSame('nosniff', $response->headers->get('X-Content-Type-Options'));
        }

        $this->get('/demo-assets/shared/sidebar-menu/unknown.js')->assertNotFound();
    }

    public function test_daily_work_menu_uses_module_assets_without_public_demo_directory(): void
    {
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/shared/sidebar-menu/menu-builder.js'));
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/shared/sidebar-menu/menu-builder.css'));
        $this->assertFileDoesNotExist(public_path('demo/shared/sidebar-menu/menu-builder.js'));
        $this->assertFileDoesNotExist(public_path('demo/shared/sidebar-menu/menu-builder.css'));

        $this->get('/demo/bao-cao-cong-viec-hang-ngay')
            ->assertOk()
            ->assertSee('/demo-assets/shared/sidebar-menu/menu-builder.js')
            ->assertSee('/demo-assets/shared/sidebar-menu/menu-builder.css');

        $this->get('/demo')->assertOk();
    }
}
