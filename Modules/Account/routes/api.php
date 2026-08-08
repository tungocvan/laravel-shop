<?php

use Illuminate\Support\Facades\Route;
use Modules\Account\Http\Controllers\Api\AccountController;

// Route::middleware('auth:sanctum')
//     ->controller(AccountController::class)
//     ->prefix('account')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('account')
    ->controller(AccountController::class)
    ->group(function () {
        Route::get('/', 'index');
    });