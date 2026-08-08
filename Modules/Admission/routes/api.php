<?php

use Illuminate\Support\Facades\Route;
use Modules\Admission\Http\Controllers\Api\AdmissionController;

// Route::middleware('auth:sanctum')
//     ->controller(AdmissionController::class)
//     ->prefix('admission')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('admission')
    ->controller(AdmissionController::class)
    ->group(function () {
        Route::get('/', 'index');
    });