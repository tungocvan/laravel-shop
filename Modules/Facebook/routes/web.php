<?php

use Illuminate\Support\Facades\Route;
use Modules\Facebook\Http\Controllers\FacebookConnectionController;
use Modules\Facebook\Http\Controllers\FacebookPageController;
use Modules\Facebook\Http\Controllers\FacebookPostController;
use Modules\Facebook\Http\Controllers\FacebookWebhookController;

Route::prefix('admin/facebook')
    ->name('admin.facebook.')
    ->middleware(['web', 'auth:admin'])
    ->group(function (): void {
        Route::get('/', [FacebookConnectionController::class, 'index'])->name('index')->middleware('permission:facebook.view,admin');
        Route::get('/connect', [FacebookConnectionController::class, 'connect'])->name('connect')->middleware('permission:facebook.connect,admin');
        Route::get('/callback', [FacebookConnectionController::class, 'callback'])->name('callback')->middleware('permission:facebook.connect,admin');
        Route::post('/disconnect', [FacebookConnectionController::class, 'disconnect'])->name('disconnect')->middleware('permission:facebook.connect,admin');
        Route::post('/sync-pages', [FacebookConnectionController::class, 'syncPages'])->name('sync-pages')->middleware('permission:facebook.pages.manage,admin');

        Route::get('/pages', [FacebookPageController::class, 'index'])->name('pages.index')->middleware('permission:facebook.pages.manage,admin');

        Route::prefix('posts')->name('posts.')->group(function (): void {
            Route::get('/', [FacebookPostController::class, 'index'])->name('index')->middleware('permission:facebook.posts.view,admin');
            Route::get('/create', [FacebookPostController::class, 'create'])->name('create')->middleware('permission:facebook.posts.create,admin');
            Route::get('/{id}', [FacebookPostController::class, 'show'])->whereNumber('id')->name('show')->middleware('permission:facebook.posts.view,admin');
            Route::get('/{id}/edit', [FacebookPostController::class, 'edit'])->whereNumber('id')->name('edit')->middleware('permission:facebook.posts.update,admin');
        });
    });

Route::middleware(['web', 'throttle:60,1'])->group(function (): void {
    Route::get('/facebook/webhook', [FacebookWebhookController::class, 'verify'])->name('facebook.webhook.verify');
    Route::post('/facebook/webhook', [FacebookWebhookController::class, 'handle'])->name('facebook.webhook.handle');
});
