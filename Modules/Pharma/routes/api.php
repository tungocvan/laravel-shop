<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\Api\PharmaController;

// Route::middleware('auth:sanctum')
//     ->controller(PharmaController::class)
//     ->prefix('pharma')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('pharma')
    ->controller(PharmaController::class)
    ->group(function () {
        Route::get('/', 'index');
    });