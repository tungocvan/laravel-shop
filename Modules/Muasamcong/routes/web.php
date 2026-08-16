<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\MuasamcongController;

Route::middleware(config('muasamcong.route_middleware', ['web', 'auth:admin']))
    ->prefix('admin/muasamcong')
    ->name('muasamcong.')
    ->group(function () {
        Route::middleware(config('muasamcong.view_middleware', ['permission:view_muasamcong,admin']))
            ->group(function () {
                Route::get('/', [MuasamcongController::class, 'index'])->name('index');
                Route::get('/contractors', [MuasamcongController::class, 'contractors'])->name('contractors');
                Route::get('/hsmt', [MuasamcongController::class, 'hsmt'])->name('hsmt');
            });

        Route::get('/config', [MuasamcongController::class, 'config'])
            ->middleware(config('muasamcong.config_middleware', ['permission:muasamcong.config.manage,admin']))
            ->name('config');
    });
