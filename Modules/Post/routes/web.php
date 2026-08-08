<?php

use Illuminate\Support\Facades\Route;
use Modules\Post\Http\Controllers\PostController;


Route::middleware(['web', 'auth:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::prefix('posts')->name('posts.')->group(function () {
        Route::get('/', [PostController::class, 'index'])
            ->middleware('permission:view_post,admin')
            ->name('index');

        Route::get('/create', [PostController::class, 'create'])
            ->middleware('permission:create_post,admin')
            ->name('create');

        Route::get('/{id}/edit', [PostController::class, 'edit'])
            ->middleware('permission:edit_post,admin')
            ->whereNumber('id')
            ->name('edit');
    });
});
