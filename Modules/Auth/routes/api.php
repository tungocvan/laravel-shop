<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\Api\AuthController;


// Route::middleware('auth:sanctum')->controller(AuthController::class)->prefix('auth')->group(function(){
//         Route::get('/', 'index');              
// });

Route::prefix('auth')->controller(AuthController::class)->group(function(){
        Route::get('/', 'index');              
});