<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\OrderController;

// Route::middleware(['web','auth:admin'])
//     ->prefix('/order')
//     ->name('order.')
//     ->group(function () {
//         Route::get('/', [OrderController::class, 'index'])->name('index');
//     });

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('orders')->name('orders.')->group(function () {
        Route::get('/', [OrderController::class, 'index'])->name('index');
        Route::get('/{id}', [OrderController::class, 'show'])->name('show');
        Route::get('/{id}/print', [OrderController::class, 'print'])->name('print');
        Route::get('/{id}/pdf', [OrderController::class, 'exportPdf'])->name('pdf');
    });
});
