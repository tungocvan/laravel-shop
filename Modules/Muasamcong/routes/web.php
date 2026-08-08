<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\MuasamcongController;

Route::middleware(config('muasamcong.route_middleware', ['web', 'auth']))->prefix('/muasamcong')->name('muasamcong.')->group(function () {
    Route::get('/', [MuasamcongController::class, 'index'])->name('index');
    Route::get('/hsmt', [MuasamcongController::class, 'hsmt'])->name('hsmt');
    Route::get('/config', [MuasamcongController::class, 'config'])->name('config');
});
