<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\MenuController;
use Modules\Admin\Http\Controllers\ProfileController;

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:view_admin,admin')
        ->name('dashboard');

    Route::prefix('menus')->middleware('permission:create_admin,admin')->name('menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])
            ->name('index');

        Route::get('/create', [MenuController::class, 'create'])
            ->name('create');

        Route::get('/{id}/edit', [MenuController::class, 'edit'])
            ->name('edit');
    });

    Route::get('/profile', [ProfileController::class, 'profile'])
        ->middleware('permission:view_admin,admin')
        ->name('profile');

    Route::get('/themes', [AdminController::class, 'themes'])
        ->middleware('permission:view_admin,admin')
        ->name('themes');

    Route::get('/layout', [AdminController::class, 'layout'])
        ->middleware('permission:create_admin,admin')
        ->name('layout');

    Route::get('/admin-header', [AdminController::class, 'adminHeader'])
        ->middleware('permission:create_admin,admin')
        ->name('header');
});
