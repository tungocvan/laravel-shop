<?php

namespace Tests\Feature;

use Tests\TestCase;

class HotelUniformDemoTest extends TestCase
{
    public function test_hotel_uniform_demo_route_and_assets(): void
    {
        $response = $this->get('/demo/quan-ly-dong-phuc-khach-san');
        $response->assertOk()
            ->assertSee('Quản lý đồng phục nhân viên khách sạn')
            ->assertSee('page-dashboard')
            ->assertSee('page-nhap')
            ->assertSee('page-cap')
            ->assertSee('page-thu')
            ->assertSee('page-settings')
            ->assertSee('data-demo-menu');
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/hotel-uniform/js/app.js'));
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/hotel-uniform/js/integrations.js'));
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/hotel-uniform/css/app.css'));
    }

    public function test_hotel_uniform_uses_own_menu_namespace(): void
    {
        $this->get('/demo/quan-ly-dong-phuc-khach-san/menu')->assertOk()->assertJsonStructure(['config']);
        $this->get('/demo/quan-ly-dong-phuc-khach-san/menu/presets')->assertOk()->assertJsonStructure(['presets']);
    }
}
