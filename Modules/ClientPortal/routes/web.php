<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;
use Modules\ClientPortal\Http\Controllers\Admin\ApplicationAdminController;
use Modules\ClientPortal\Http\Controllers\PortalController;

Route::middleware(['web', 'auth:web'])->group(function () {
    Route::get('/my-apps', [PortalController::class, 'index'])->name('client.apps.index');

    Route::middleware('client.application:muasamcong')
        ->prefix('apps/muasamcong')
        ->name('client.muasamcong.')
        ->group(function () {
            Route::get('/', [MuasamcongApplicationController::class, 'dashboard'])->name('dashboard');
            Route::get('/drug-pricing', [MuasamcongApplicationController::class, 'drugPricing'])
                ->middleware('client.feature:muasamcong,drug-pricing')
                ->name('drug-pricing');
            Route::get('/drug-pricing/{sourceId}', [MuasamcongApplicationController::class, 'drugPricingDetail'])
                ->middleware('client.feature:muasamcong,drug-pricing')
                ->name('drug-pricing.detail');
            Route::post('/drug-pricing/sync', [MuasamcongApplicationController::class, 'queueDrugPricingSync'])
                ->middleware('client.feature:muasamcong,drug-pricing')
                ->name('drug-pricing.sync');
        });
});

Route::middleware(['web', 'auth:admin'])->prefix('admin/client-apps')->name('admin.client-apps.')->group(function () {
    Route::get('/', [ApplicationAdminController::class, 'index'])->middleware('permission:view_role,admin')->name('index');
    Route::post('/sync-permissions', [ApplicationAdminController::class, 'syncPermissions'])->middleware('permission:edit_role,admin')->name('sync-permissions');
    Route::post('/sync-super-admin', [ApplicationAdminController::class, 'syncSuperAdmin'])->middleware('permission:edit_role,admin')->name('sync-super-admin');
    Route::get('/users/{user}', [ApplicationAdminController::class, 'editUser'])->middleware('permission:edit_user,admin')->name('users.edit');
    Route::put('/users/{user}', [ApplicationAdminController::class, 'updateUser'])->middleware('permission:edit_user,admin')->name('users.update');
    Route::get('/roles/{role}', [ApplicationAdminController::class, 'editRole'])->middleware('permission:edit_role,admin')->name('roles.edit');
    Route::put('/roles/{role}', [ApplicationAdminController::class, 'updateRole'])->middleware('permission:edit_role,admin')->name('roles.update');
});
