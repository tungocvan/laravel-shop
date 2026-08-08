<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\Api\MuasamcongController;

// Route::middleware('auth:sanctum')->controller(MuasamcongController::class)->prefix('muasamcong')->group(function(){
//         Route::get('/', 'index');
// });

Route::middleware(config('muasamcong.api_middleware', ['api', 'auth:sanctum']))->prefix('muasamcong')->controller(MuasamcongController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/search-pricing', 'searchPricing');
});
