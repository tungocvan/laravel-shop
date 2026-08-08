<?php

use Illuminate\Support\Facades\Route;
use Modules\System\Http\Controllers\DatabaseController;
use Modules\System\Http\Controllers\EnvConfigController;
use Modules\System\Http\Controllers\SettingController;
use Modules\System\Http\Controllers\SystemController;

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('/system')->name('system.')->group(function () {
        Route::get('/', [SystemController::class, 'index'])
            ->middleware('permission:system.manage,admin')
            ->name('index');
        Route::get('/modules', [SettingController::class, 'modules'])
            ->middleware('permission:system.modules.view,admin')
            ->name('modules');
        Route::get('/settings', [SettingController::class, 'index'])
            ->middleware('permission:system.settings.view,admin')
            ->name('settings.index');
        Route::get('/settings/env', [EnvConfigController::class, 'index'])
            ->middleware('permission:system.env.view,admin')
            ->name('settings.env');

        Route::prefix('/database')->name('database.')->group(function () {
            Route::get('/', [DatabaseController::class, 'index'])
                ->middleware('permission:database.view,admin')
                ->name('index');
            Route::get('/backup-restore', [DatabaseController::class, 'backupRestore'])
                ->middleware('permission:database.view,admin')
                ->name('backup-restore');
            Route::get('/download/{filename}', [DatabaseController::class, 'download'])
                ->middleware('permission:database.download,admin')
                ->name('download')
                ->where('filename', '[A-Za-z0-9_.-]+');
        });
    });
});
