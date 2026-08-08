<?php

use Illuminate\Support\Facades\Route;
use Modules\Account\Http\Controllers\AccountController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/accounts')
    ->name('admin.accounts.')
    ->group(function () {
        Route::get('/', [AccountController::class, 'index'])
            ->name('index');

        Route::get('/create', [AccountController::class, 'create'])
            ->name('create');

        Route::get('/{id}/edit', [AccountController::class, 'edit'])
            ->whereNumber('id')
            ->name('edit');
    });
