<?php

use Illuminate\Support\Facades\Route;
use Modules\User\Http\Controllers\Api\UsersController;


// Route::middleware('auth:sanctum')->controller(UsersController::class)->prefix('users')->group(function(){
//         Route::get('/', 'index');              
// });

Route::prefix('users')->controller(UsersController::class)->group(function(){
        Route::get('/', 'index');              
});
