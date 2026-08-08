<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\IdentityController;

Route::middleware(['web', 'auth:admin', 'permission:view_identity'])
    ->prefix('admin/identities')
    ->name('admin.identities.')
    ->group(function () {
        Route::get('/', [IdentityController::class, 'index'])->name('index');

        Route::middleware('permission:create_identity')->group(function () {
            Route::get('/create', [IdentityController::class, 'create'])->name('create');
        });

        Route::middleware('permission:edit_identity')->group(function () {
            Route::get('/{identity}/edit', [IdentityController::class, 'edit'])
                ->whereNumber('identity')
                ->name('edit');
        });
    });
