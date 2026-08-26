<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Request\Http\Controllers\RequestApplicationController;
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
    });
}
