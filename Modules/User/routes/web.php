<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\UserController;

Route::middleware(['web', 'auth:admin', 'permission:view_user,admin'])
    ->prefix('admin/user')
    ->name('admin.user.')
    ->group(function () {
        Route::get('/', [UserController::class, 'index'])->name('index');

        Route::middleware('permission:create_user,admin')->group(function () {
            Route::get('/create', [UserController::class, 'create'])->name('create');
        });

        Route::middleware('permission:edit_user,admin')->group(function () {
            Route::get('/{id}/edit', [UserController::class, 'edit'])
                ->whereNumber('id')
                ->name('edit');
        });
});
