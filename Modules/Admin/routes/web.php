<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\DashboardController;
use Modules\Admin\Http\Controllers\MenuController;
use Modules\Admin\Http\Controllers\ProfileController;

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware('permission:admin.dashboard.view,admin')
        ->name('dashboard');

    Route::prefix('menus')->name('menus.')->group(function () {
        Route::get('/', [MenuController::class, 'index'])
            ->middleware('permission:admin.menu.view,admin')
            ->name('index');

        Route::get('/create', [MenuController::class, 'create'])
            ->middleware('permission:admin.menu.create,admin')
            ->name('create');

        Route::get('/{id}/edit', [MenuController::class, 'edit'])
            ->middleware('permission:admin.menu.update,admin')
            ->name('edit');
    });

    Route::get('/profile', [ProfileController::class, 'profile'])
        ->middleware('permission:admin.profile.view,admin')
        ->name('profile');

    Route::get('/themes', fn () => redirect()->route('admin.layout.design'))
        ->middleware('permission:admin.layout.view,admin')
        ->name('themes');

    Route::get('/layout', [AdminController::class, 'layout'])
        ->middleware('permission:admin.layout.view,admin')
        ->name('layout');

    Route::prefix('layout')->name('layout.')->middleware('permission:admin.layout.view,admin')->group(function () {
        Route::get('/general', [AdminController::class, 'layoutGeneral'])->name('general');
        Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header');
        Route::get('/sidebar', [AdminController::class, 'layoutSidebar'])->name('sidebar');
        Route::get('/footer', [AdminController::class, 'layoutFooter'])->name('footer');
        Route::get('/design', [AdminController::class, 'layoutDesign'])->name('design');
        Route::get('/navigation', [AdminController::class, 'layoutNavigation'])->name('navigation');
    });
});
