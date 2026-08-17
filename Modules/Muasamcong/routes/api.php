<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\Api\MuasamcongController;
use Modules\Muasamcong\Http\Controllers\Api\PersonalSessionImportController;

Route::post('muasamcong/update-cookie', PersonalSessionImportController::class)
    ->middleware(['api', 'throttle:6,1'])
    ->name('muasamcong.session-import');

Route::middleware(config('muasamcong.api_middleware', ['api', 'auth:sanctum']))->prefix('muasamcong')->controller(MuasamcongController::class)->group(function () {
    Route::get('/', 'index');
    Route::post('/search-pricing', 'searchPricing');
});
