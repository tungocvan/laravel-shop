<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomAssetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomDatasetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomManagementController;

Route::get('/demo-assets/quan-ly-phong-tro/{asset}', RentalRoomAssetController::class)
    ->where('asset', 'css/app\\.css|js/app\\.js|js/integrations\\.js|shared/menu-editor\\.css|shared/menu-editor\\.js')
    ->name('demo.rental-room-management.asset');

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-phong-tro', RentalRoomManagementController::class)->name('demo.rental-room-management');
    Route::get('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.rental-room-management.datasets');
    Route::get('/demo/quan-ly-phong-tro/datasets/{dataset}', [RentalRoomDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.rental-room-management.datasets.show');
    Route::post('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.rental-room-management.datasets.store');
});
