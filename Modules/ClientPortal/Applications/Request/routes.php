<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Request\Http\Controllers\RequestApplicationController;
use Modules\Request\Http\Controllers\RequestAttachmentController;
use Modules\Request\Http\Middleware\UseRequestAuthorizationGuard;

if ((bool) config('modules.registry.Request.enabled', false)) {
    Route::middleware([
        'web',
        'auth:web',
        'client.application:request',
        UseRequestAuthorizationGuard::class.':web',
    ])->prefix('apps/request')->name('client.request.')->group(function (): void {
        Route::get('/', [RequestApplicationController::class, 'dashboard'])
            ->middleware('client.feature:request,overview')
            ->name('dashboard');

        Route::get('/catalog', [RequestApplicationController::class, 'catalog'])
            ->middleware('client.feature:request,create')
            ->name('catalog');
        Route::get('/catalog/{typePublicId}', [RequestApplicationController::class, 'create'])
            ->middleware('client.feature:request,create')
            ->name('create');

        Route::get('/mine', [RequestApplicationController::class, 'mine'])
            ->middleware('client.feature:request,mine')
            ->name('mine');
        Route::get('/mine/{requestPublicId}', [RequestApplicationController::class, 'show'])
            ->middleware('client.feature:request,mine')
            ->name('show');
        Route::get('/mine/{requestPublicId}/attachments/{attachmentPublicId}', RequestAttachmentController::class)
            ->middleware(['client.feature:request,mine', 'throttle:request-download'])
            ->name('attachments.download');

        Route::get('/inbox', [RequestApplicationController::class, 'inbox'])
            ->middleware('client.feature:request,inbox')
            ->name('inbox');
        Route::get('/processed', [RequestApplicationController::class, 'processed'])
            ->middleware('client.feature:request,processed')
            ->name('processed');
    });
}
