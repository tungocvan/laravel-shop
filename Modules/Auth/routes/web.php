<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\ClientGoogleController;
use Modules\Auth\Http\Controllers\GoogleController;

Route::middleware(['web'])->group(function () {
    Route::get('/admin/login', [AuthController::class, 'adminLogin'])->name('admin.login');
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('/login', [AuthController::class, 'clientLogin'])->name('login');
    Route::get('/my-apps/auth/google', [ClientGoogleController::class, 'redirect'])->name('client.apps.google');
    Route::get('/my-apps/auth/google/callback', [ClientGoogleController::class, 'callback'])->name('client.apps.google.callback');

    Route::middleware('auth:web')->group(function () {
        Route::get('/my-apps/auth/google/link', [ClientGoogleController::class, 'linkRedirect'])->name('client.apps.google.link');
        Route::post('/logout', [AuthController::class, 'clientLogout'])->name('logout');
    });
});

Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'adminLogout'])->name('logout');
});
