<?php

use Illuminate\Support\Facades\Route;
use Modules\Order\Http\Controllers\Api\OrderController;

// Route::middleware('auth:sanctum')
//     ->controller(OrderController::class)
//     ->prefix('order')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('order')
    ->controller(OrderController::class)
    ->group(function () {
        Route::get('/', 'index');
    });