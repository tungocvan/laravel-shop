<?php

use Illuminate\Support\Facades\Route;
//use Modules\Website\Http\Controllers\AuthController;


// Route::get('/admin', [\App\Http\Controllers\Admin\DashboardController::class, 'index'])->name('admin.dashboard');
// Route::get('/login', [\App\Http\Controllers\Auth\LoginController::class, 'showLoginForm'])->name('login');
// Route::post('/login', [\App\Http\Controllers\Auth\LoginController::class, 'login'])->name('login.perform');

// Route::get('/login', [AuthController::class, 'login'])->name('login');

// Route::middleware(['web','auth'])->group(function(){
//     Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
// });

// Route::prefix('admin')->middleware('auth')->group(function() {
//     Route::resource('users', \App\Http\Controllers\Admin\UserController::class)
//         ->names('admin.users');
// });

// Route::middleware('web')->post('/admin/logout', [\App\Http\Controllers\Auth\LoginController::class, 'logout'])->name('logout');

// Route::middleware(['web'])->get('/template', function () {
//         return view('template');
// })->name('template');

// Route::middleware(['web'])->get('/', function () {
//         return view('welcome');
// });
