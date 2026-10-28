<?php

namespace Tests\Feature;

use Tests\TestCase;

final class EnterpriseManagementReportDemoTest extends TestCase
{
    public function test_demo_renders_with_assets_and_original_dom_ids(): void
    {
        $response = $this->get('/demo/bao-cao-quan-tri-doanh-nghiep');

        $response->assertOk()
            ->assertSee('id="sb"', false)
            ->assertSee('id="content"', false)
            ->assertSee('id="du-lieu"', false)
            ->assertSee('demo-assets/bao-cao-quan-tri-doanh-nghiep/css/app.css', false)
            ->assertSee('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/app.js', false);
    }

    public function test_demo_has_only_one_ui_route_and_assets_are_separate(): void
    {
        $route = app('router')->getRoutes()->getByName('demo.enterprise-management-report');
        $this->assertNotNull($route);
        $this->assertSame(['GET', 'HEAD'], $route->methods());
        $this->assertSame('demo/bao-cao-quan-tri-doanh-nghiep', $route->uri());
        $this->assertFileDoesNotExist(public_path('demo/bao-cao-quan-tri-doanh-nghiep/index.html'));
        $this->assertFileExists(public_path('demo-assets/bao-cao-quan-tri-doanh-nghiep/js/app.js'));
        $this->assertFileExists(public_path('demo-assets/bao-cao-quan-tri-doanh-nghiep/original.html'));
    }
}
