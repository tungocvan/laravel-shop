<?php

use Illuminate\Support\Facades\Route;
use Modules\Product\Http\Controllers\ProductController;
use Modules\Product\Http\Controllers\ProductCommissionController;


Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('products')->name('products.')->group(function () {
            Route::get('/', [ProductController::class, 'index'])
                ->middleware('permission:view_product,admin')
                ->name('index');

            Route::get('/create', [ProductController::class, 'create'])
                ->middleware('permission:create_product,admin')
                ->name('create');

            Route::get('/{id}/edit', [ProductController::class, 'edit'])
                ->middleware('permission:edit_product,admin')
                ->whereNumber('id')
                ->name('edit');

            Route::prefix('{productId}')->whereNumber('productId')->group(function () {
                Route::get('commissions', [ProductCommissionController::class, 'index'])
                    ->middleware('permission:edit_product,admin')
                    ->name('commissions');
            });
    });
});
