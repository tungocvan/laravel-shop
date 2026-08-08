<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\Api\ChatController;

// Route::middleware('auth:sanctum')
//     ->controller(ChatController::class)
//     ->prefix('chat')
//     ->group(function () {
//         Route::get('/', 'index');
//     });

Route::prefix('chat')
    ->controller(ChatController::class)
    ->group(function () {
        Route::get('/', 'index');
    });