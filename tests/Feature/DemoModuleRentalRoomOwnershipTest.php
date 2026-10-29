<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomAssetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomDatasetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomManagementController;
use Tests\TestCase;

final class DemoModuleRentalRoomOwnershipTest extends TestCase
{
    public function test_rental_room_routes_are_owned_by_demo_module(): void
    {
        $ui = Route::getRoutes()->getByName('demo.rental-room-management');
        $this->assertNotNull($ui);
        $this->assertSame(RentalRoomManagementController::class, $ui->getActionName());
        $this->assertSame('demo/quan-ly-phong-tro', $ui->uri());
        $this->assertContains('web', $ui->gatherMiddleware());

        foreach ([
            'demo.rental-room-management.datasets',
            'demo.rental-room-management.datasets.show',
            'demo.rental-room-management.datasets.store',
        ] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertStringStartsWith(RentalRoomDatasetController::class.'@', $route->getActionName());
        }
    }

    public function test_module_view_and_public_assets_are_available(): void
    {
        $this->assertTrue(view()->exists('demo::projects.rental-room.index'));
        $this->assertFileExists(base_path('Modules/Demo/resources/assets/projects/rental-room/js/app.js'));
        $this->assertFileDoesNotExist(public_path('demo-assets/quan-ly-phong-tro/js/app.js'));
        $asset = Route::getRoutes()->getByName('demo.rental-room-management.asset');
        $this->assertNotNull($asset);
        $this->assertSame(RentalRoomAssetController::class, $asset->getActionName());
    }
}
