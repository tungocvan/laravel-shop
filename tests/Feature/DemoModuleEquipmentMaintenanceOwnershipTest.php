<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceAssetController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceDatasetController;
use Tests\TestCase;

final class DemoModuleEquipmentMaintenanceOwnershipTest extends TestCase
{
    public function test_equipment_ui_and_datasets_are_owned_by_demo_module(): void
    {
        $ui=Route::getRoutes()->getByName('demo.equipment-maintenance');
        $this->assertNotNull($ui);
        $this->assertSame('demo/quan-ly-thiet-bi-bao-tri', $ui->uri());
        $this->assertSame(EquipmentMaintenanceController::class, $ui->getActionName());
        foreach (['demo.equipment-maintenance.datasets','demo.equipment-maintenance.datasets.show','demo.equipment-maintenance.datasets.store'] as $name) {
            $route=Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringStartsWith(EquipmentMaintenanceDatasetController::class.'@', $route->getActionName());
        }
    }

    public function test_equipment_assets_are_allowlisted_and_rendered_from_module(): void
    {
        $this->assertTrue(view()->exists('demo::projects.equipment-maintenance.index'));
        $this->assertSame(EquipmentMaintenanceAssetController::class, Route::getRoutes()->getByName('demo.equipment-maintenance.asset')->getActionName());
        $this->get('/demo/quan-ly-thiet-bi-bao-tri')->assertOk()
            ->assertSee('data-demo-menu="quan-ly-thiet-bi-bao-tri"', false)
            ->assertSee('id="sb"', false);
        $this->get('/demo-assets/quan-ly-thiet-bi-bao-tri/js/app.js')->assertOk()->assertHeader('X-Content-Type-Options','nosniff');
        $this->get('/demo-assets/quan-ly-thiet-bi-bao-tri/js/integrations.js')->assertOk();
        $this->get('/demo-assets/quan-ly-thiet-bi-bao-tri/shared/menu-editor.js')->assertOk();
        $this->get('/demo-assets/quan-ly-thiet-bi-bao-tri/unknown.js')->assertNotFound();
    }

    public function test_server_snapshot_requires_secret(): void
    {
        $this->assertContains($this->get('/demo/quan-ly-thiet-bi-bao-tri/datasets')->status(), [403,503]);
    }
}
