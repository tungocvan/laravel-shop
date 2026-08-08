<?php

use Illuminate\Support\Facades\Route;
use Modules\Website\Http\Controllers\Api\WebsiteController;


// Route::middleware('auth:sanctum')->controller(WebsiteController::class)->prefix('website')->group(function(){
//         Route::get('/', 'index');              
// });

Route::prefix('website')->controller(WebsiteController::class)->group(function(){
        Route::get('/', 'index');              
});