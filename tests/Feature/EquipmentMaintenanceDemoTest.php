<?php

namespace Tests\Feature;

use Tests\TestCase;

final class EquipmentMaintenanceDemoTest extends TestCase
{
    public function test_equipment_maintenance_demo_renders(): void
    {
        $this->get('/demo/quan-ly-thiet-bi-bao-tri')->assertOk()
            ->assertSee('demo-assets/quan-ly-thiet-bi-bao-tri/js/app.js', false)
            ->assertSee('id="sb"', false);
    }
}
