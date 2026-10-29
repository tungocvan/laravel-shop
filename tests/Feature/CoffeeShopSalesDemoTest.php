<?php
namespace Tests\Feature;
use Tests\TestCase;
class CoffeeShopSalesDemoTest extends TestCase
{
    public function test_demo_renders_and_assets_are_versioned(): void
    {
        $this->get('/demo/quan-ly-ban-hang-ca-phe')->assertOk()
            ->assertSee('demo-assets/quan-ly-ban-hang-ca-phe/css/app.css?v=', false)
            ->assertSee('demo-assets/quan-ly-ban-hang-ca-phe/js/app.js?v=', false)
            ->assertSee('du-lieu', false);
    }
    public function test_assets_do_not_shadow_route(): void
    {
        $this->assertDirectoryDoesNotExist(public_path('demo/quan-ly-ban-hang-ca-phe'));
        $this->assertFileExists(public_path('demo-assets/quan-ly-ban-hang-ca-phe/js/app.js'));
    }
}
