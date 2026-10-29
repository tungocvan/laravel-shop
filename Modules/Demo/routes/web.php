<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomAssetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomDatasetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomManagementController;

Route::get('/demo-assets/quan-ly-phong-tro/{asset}', RentalRoomAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js|shared/theme-selector\\.js')
    ->name('demo.rental-room-management.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-phong-tro', RentalRoomManagementController::class)->name('demo.rental-room-management');
    Route::get('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.rental-room-management.datasets');
    Route::get('/demo/quan-ly-phong-tro/datasets/{dataset}', [RentalRoomDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.rental-room-management.datasets.show');
    Route::post('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.rental-room-management.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformDatasetController;
use Modules\Demo\Http\Controllers\Projects\HotelUniform\HotelUniformAssetController;

Route::get('/demo-assets/quan-ly-dong-phuc-khach-san/{asset}', HotelUniformAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.hotel-uniform.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-dong-phuc-khach-san', HotelUniformController::class)->name('demo.hotel-uniform');
    Route::get('/demo/quan-ly-dong-phuc-khach-san/datasets', [HotelUniformDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.hotel-uniform.datasets');
    Route::get('/demo/quan-ly-dong-phuc-khach-san/datasets/{dataset}', [HotelUniformDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.hotel-uniform.datasets.show');
    Route::post('/demo/quan-ly-dong-phuc-khach-san/datasets', [HotelUniformDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.hotel-uniform.datasets.store');
});

use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceDatasetController;
use Modules\Demo\Http\Controllers\Projects\EquipmentMaintenance\EquipmentMaintenanceAssetController;

Route::get('/demo-assets/quan-ly-thiet-bi-bao-tri/{asset}', EquipmentMaintenanceAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.equipment-maintenance.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-thiet-bi-bao-tri', EquipmentMaintenanceController::class)->name('demo.equipment-maintenance');
    Route::get('/demo/quan-ly-thiet-bi-bao-tri/datasets', [EquipmentMaintenanceDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.equipment-maintenance.datasets');
    Route::get('/demo/quan-ly-thiet-bi-bao-tri/datasets/{dataset}', [EquipmentMaintenanceDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.equipment-maintenance.datasets.show');
    Route::post('/demo/quan-ly-thiet-bi-bao-tri/datasets', [EquipmentMaintenanceDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.equipment-maintenance.datasets.store');
});
