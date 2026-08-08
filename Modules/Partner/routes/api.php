<?php

use Illuminate\Support\Facades\Route;
use Modules\Partner\Http\Controllers\Api\PartnerController;

// Route::middleware('auth:sanctum')
//     ->controller(PartnerController::class)
//     ->prefix('partner')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('partner')
    ->controller(PartnerController::class)
    ->group(function () {
        Route::get('/', 'index');
    });