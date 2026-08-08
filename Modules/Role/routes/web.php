<?php

use Illuminate\Support\Facades\Route;
use Modules\Role\Http\Controllers\RoleController;

// Route::middleware(['web','auth'])->prefix('/role')->name('role.')->group(function(){
//     Route::get('/', [RoleController::class,'index'])->name('index');
// });

Route::middleware(['web', 'auth:admin'])->group(function () {
    Route::redirect('/admin/role', '/admin/roles');
    Route::redirect('/admin/role/create', '/admin/roles/create');
    Route::get('/admin/role/{id}/edit', fn ($id) => redirect('/admin/roles/'.$id.'/edit'))
        ->whereNumber('id');
});

Route::middleware(['web','auth:admin'])->prefix('admin/roles')->name('admin.role.')->group(function () {
   Route::get('/', [RoleController::class, 'index'])->name('index');
   Route::get('/create', [RoleController::class, 'create'])->name('create');
   Route::get('/{id}/edit', [RoleController::class, 'edit'])->name('edit');
});
