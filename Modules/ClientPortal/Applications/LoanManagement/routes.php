<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanApplicationController;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanWorkspaceController;

if ((bool) config('modules.registry.LoanManagement.enabled', false)) {
    Route::middleware(['web', 'auth:web', 'client.application:loans'])
        ->prefix('apps/loans')->name('client.loans.')
        ->group(function (): void {
            Route::get('/', [LoanApplicationController::class, 'dashboard'])
                ->middleware('client.feature:loans,overview')
                ->name('dashboard');
            Route::post('/lenders', [LoanWorkspaceController::class, 'storeLender'])->name('lenders.store');
            Route::post('/contracts', [LoanWorkspaceController::class, 'storeContract'])->name('contracts.store');
            Route::put('/contracts/{contract}', [LoanWorkspaceController::class, 'updateContract'])->whereNumber('contract')->name('contracts.update');
        });
}
