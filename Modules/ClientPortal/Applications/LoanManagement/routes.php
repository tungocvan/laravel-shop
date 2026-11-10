<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanApplicationController;

if ((bool) config('modules.registry.LoanManagement.enabled', false)) {
    Route::middleware(['web', 'auth:web', 'client.application:loans'])
        ->prefix('apps/loans')->name('client.loans.')
        ->group(function (): void {
            Route::get('/', [LoanApplicationController::class, 'dashboard'])
                ->middleware('client.feature:loans,overview')
                ->name('dashboard');
        });
}
