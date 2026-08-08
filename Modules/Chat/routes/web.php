<?php

use Illuminate\Support\Facades\Route;
use Modules\Chat\Http\Controllers\ChatController;

Route::middleware(['web','auth:admin'])
    ->prefix('/admin')
    ->name('admin.chat.')
    ->group(function () {
        Route::get('/chat/internal-chat', [ChatController::class, 'internalChat'])->name('index');       
        Route::get('/chat', [ChatController::class, 'chat'])->name('cskh');   
});  