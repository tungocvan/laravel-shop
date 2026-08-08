<?php

use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\Api\RoleController;


// Route::middleware('auth:sanctum')->controller(RoleController::class)->prefix('role')->group(function(){
//         Route::get('/', 'index');              
// });

Route::prefix('role')->controller(RoleController::class)->group(function(){
        Route::get('/', 'index');              
});