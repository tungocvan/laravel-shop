<?php

use Illuminate\Support\Facades\Route;
use Modules\Auth\Http\Controllers\AuthController;
use Modules\Auth\Http\Controllers\GoogleController;

Route::middleware(['web'])->group(function(){
    Route::get('/admin/login', [AuthController::class, 'login'])->name('admin.login');
    Route::get('/auth/google', [GoogleController::class, 'redirectToGoogle'])->name('google');
    Route::get('/auth/google/callback', [GoogleController::class, 'handleGoogleCallback'])->name('google.callback');
    Route::get('/login', [AuthController::class, 'login'])->name('login');
    Route::get('/register', [AuthController::class, 'register'])->name('register');
});


 Route::middleware(['web','auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
 });
