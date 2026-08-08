<?php

use Illuminate\Support\Facades\Route;
use Modules\Identity\Http\Controllers\Api\IdentityController;

Route::middleware(['api', 'auth:sanctum'])
    ->prefix('identities')
    ->name('api.identities.')
    ->controller(IdentityController::class)
    ->group(function () {
        Route::get('/', 'index')->middleware('permission:view_identity')->name('index');
    });
