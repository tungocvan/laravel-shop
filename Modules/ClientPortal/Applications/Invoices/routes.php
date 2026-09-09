<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Invoices\Http\Controllers\InvoicesApplicationController;

if ((bool) config('modules.registry.Invoices.enabled', false)) {
    Route::middleware([
        'web',
        'auth:web',
        'client.application:invoices',
    ])->prefix('apps/invoices')->name('client.invoices.')->group(function (): void {
        Route::get('/', [InvoicesApplicationController::class, 'dashboard'])
            ->middleware('client.feature:invoices,overview')
            ->name('dashboard');

        Route::get('/partners', [InvoicesApplicationController::class, 'partners'])
            ->middleware('client.feature:invoices,partners')
            ->name('partners');

        Route::get('/list', [InvoicesApplicationController::class, 'index'])
            ->middleware('client.feature:invoices,list')
            ->name('index');
        Route::get('/list/{invoice}', [InvoicesApplicationController::class, 'show'])
            ->middleware('client.feature:invoices,list')
            ->name('show');
        Route::post('/export', [InvoicesApplicationController::class, 'export'])
            ->middleware('client.feature:invoices,list')
            ->name('export');
        Route::get('/list/{invoice}/pdf', [InvoicesApplicationController::class, 'pdf'])
            ->middleware('client.feature:invoices,list')
            ->name('pdf');

        Route::get('/sync', [InvoicesApplicationController::class, 'sync'])
            ->middleware('client.feature:invoices,sync')
            ->name('sync');
        Route::post('/sync/captcha', [InvoicesApplicationController::class, 'refreshSyncCaptcha'])
            ->middleware(['client.feature:invoices,sync', 'throttle:10,1'])
            ->name('sync.captcha');
        Route::post('/sync/authenticate', [InvoicesApplicationController::class, 'authenticateSync'])
            ->middleware(['client.feature:invoices,sync', 'throttle:10,1'])
            ->name('sync.authenticate');
        Route::post('/sync', [InvoicesApplicationController::class, 'startSync'])
            ->middleware(['client.feature:invoices,sync', 'throttle:10,1'])
            ->name('sync.start');
    });
}
