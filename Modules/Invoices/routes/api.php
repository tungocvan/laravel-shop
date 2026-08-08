<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Http\Controllers\Api\InvoicesController;

// Route::middleware('auth:sanctum')->controller(InvoicesController::class)->prefix('invoices')->group(function(){
//         Route::get('/', 'index');
// });

Route::middleware(config('invoices.api_middleware', ['api', 'auth:sanctum']))->prefix('invoices')->controller(InvoicesController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/', 'filter');
});
