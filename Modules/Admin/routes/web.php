<?php

use Illuminate\Support\Facades\Route;
use Modules\Admin\Http\Controllers\AdminController;
use Modules\Admin\Http\Controllers\ClientApplicationAdminController;
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

    Route::prefix('client-apps')->name('client-apps.')->group(function () {
        Route::get('/', [ClientApplicationAdminController::class, 'index'])
            ->middleware('permission:view_role,admin')
            ->name('index');

        Route::post('/sync-permissions', [ClientApplicationAdminController::class, 'syncPermissions'])
            ->middleware('permission:edit_role,admin')
            ->name('sync-permissions');

        Route::post('/sync-super-admin', [ClientApplicationAdminController::class, 'syncSuperAdmin'])
            ->middleware('permission:edit_role,admin')
            ->name('sync-super-admin');

        Route::get('/users/{user}', [ClientApplicationAdminController::class, 'editUser'])
            ->middleware('permission:edit_user,admin')
            ->name('users.edit');
        Route::put('/users/{user}', [ClientApplicationAdminController::class, 'updateUser'])
            ->middleware('permission:edit_user,admin')
            ->name('users.update');

        Route::get('/roles/{role}', [ClientApplicationAdminController::class, 'editRole'])
            ->middleware('permission:edit_role,admin')
            ->name('roles.edit');
        Route::put('/roles/{role}', [ClientApplicationAdminController::class, 'updateRole'])
            ->middleware('permission:edit_role,admin')
            ->name('roles.update');
    });

    Route::get('/profile', [ProfileController::class, 'profile'])
        ->middleware('permission:admin.profile.view,admin')
        ->name('profile');

    Route::get('/themes', [AdminController::class, 'themes'])
        ->middleware('permission:admin.theme.view,admin')
        ->name('themes');

    Route::get('/layout', [AdminController::class, 'layout'])
        ->middleware('permission:admin.layout.view,admin')
        ->name('layout');

    Route::get('/admin-header', [AdminController::class, 'adminHeader'])
        ->middleware('permission:admin.header.view,admin')
        ->name('header');
});
