<?php

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Http\Controllers\Admin\ApplicationAdminController;
use Modules\ClientPortal\Http\Controllers\Admin\PwaSettingsController;
use Modules\ClientPortal\Http\Controllers\PortalController;

Route::middleware(['web'])->group(function () {
    Route::get('/my-apps/login', [PortalController::class, 'login'])->name('client.apps.login');

    Route::middleware('auth:web')->group(function () {
        Route::get('/my-apps', [PortalController::class, 'index'])->name('client.apps.index');
    });
});

Route::middleware(['web', 'auth:admin'])->prefix('admin/client-apps')->name('admin.client-apps.')->group(function () {
    Route::get('/', [ApplicationAdminController::class, 'index'])->middleware('permission:view_role,admin')->name('index');
    Route::post('/sync-permissions', [ApplicationAdminController::class, 'syncPermissions'])->middleware('permission:edit_role,admin')->name('sync-permissions');
    Route::post('/sync-super-admin', [ApplicationAdminController::class, 'syncSuperAdmin'])->middleware('permission:edit_role,admin')->name('sync-super-admin');

    Route::get('/pwa', [PwaSettingsController::class, 'edit'])->middleware('permission:edit_role,admin')->name('pwa.edit');
    Route::put('/pwa/general', [PwaSettingsController::class, 'updateGeneral'])->middleware('permission:edit_role,admin')->name('pwa.general.update');
    Route::put('/pwa/login', [PwaSettingsController::class, 'updateLogin'])->middleware('permission:edit_role,admin')->name('pwa.login.update');
    Route::put('/pwa/launcher', [PwaSettingsController::class, 'updateLauncher'])->middleware('permission:edit_role,admin')->name('pwa.launcher.update');
    Route::put('/pwa/applications/{application}', [PwaSettingsController::class, 'updateApplication'])->middleware('permission:edit_role,admin')->name('pwa.applications.update');

    Route::get('/users/{user}', [ApplicationAdminController::class, 'editUser'])->middleware('permission:edit_user,admin')->name('users.edit');
    Route::put('/users/{user}', [ApplicationAdminController::class, 'updateUser'])->middleware('permission:edit_user,admin')->name('users.update');
    Route::get('/roles/{role}', [ApplicationAdminController::class, 'editRole'])->middleware('permission:edit_role,admin')->name('roles.edit');
    Route::put('/roles/{role}', [ApplicationAdminController::class, 'updateRole'])->middleware('permission:edit_role,admin')->name('roles.update');
});

foreach (File::glob(base_path('Modules/ClientPortal/Applications/*/routes.php')) as $applicationRoutes) {
    require $applicationRoutes;
}
