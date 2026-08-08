<?php

use Illuminate\Support\Facades\Route;
use Modules\Category\Http\Controllers\CategoryController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('/admin/category')
    ->name('admin.category.')
    ->group(function () {
        Route::get('/', [CategoryController::class, 'index'])
            ->middleware('permission:view_category,admin')
            ->name('index');

        Route::get('/create', [CategoryController::class, 'create'])
            ->middleware('permission:create_category,admin')
            ->name('create');

        Route::get('/{id}/edit', [CategoryController::class, 'edit'])
            ->whereNumber('id')
            ->middleware('permission:edit_category,admin')
            ->name('edit');
    });
