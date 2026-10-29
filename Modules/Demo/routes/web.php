<?php

use Illuminate\Support\Facades\Route;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomDatasetController;
use Modules\Demo\Http\Controllers\Projects\RentalRoom\RentalRoomManagementController;

Route::middleware('web')->group(function (): void {
    Route::get('/demo/quan-ly-phong-tro', RentalRoomManagementController::class)->name('demo.rental-room-management');
    Route::get('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'index'])->middleware('throttle:20,1')->name('demo.rental-room-management.datasets');
    Route::get('/demo/quan-ly-phong-tro/datasets/{dataset}', [RentalRoomDatasetController::class, 'show'])->middleware('throttle:20,1')->where('dataset', '[a-z0-9-]{1,80}')->name('demo.rental-room-management.datasets.show');
    Route::post('/demo/quan-ly-phong-tro/datasets', [RentalRoomDatasetController::class, 'store'])->middleware('throttle:5,1')->name('demo.rental-room-management.datasets.store');
});
