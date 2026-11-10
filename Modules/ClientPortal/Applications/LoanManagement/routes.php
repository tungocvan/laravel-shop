<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanApplicationController;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanWorkspaceController;
use Modules\ClientPortal\Applications\LoanManagement\Http\Controllers\LoanInsuranceController;

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
            Route::delete('/contracts/{contract}', [LoanWorkspaceController::class, 'deleteContract'])->whereNumber('contract')->name('contracts.destroy');
            Route::patch('/contracts/{contract}/restore-draft', [LoanWorkspaceController::class, 'restoreDraft'])->whereNumber('contract')->name('contracts.restore-draft');
            Route::post('/contracts/{contract}/disbursements', [LoanWorkspaceController::class, 'disburse'])->whereNumber('contract')->name('disbursements.store');
            Route::post('/contracts/{contract}/schedules', [LoanWorkspaceController::class, 'schedule'])->whereNumber('contract')->name('schedules.store');
            Route::post('/contracts/{contract}/schedules/{schedule}/payments', [LoanWorkspaceController::class, 'payInstallment'])->whereNumber('contract')->whereNumber('schedule')->name('installments.pay');
            Route::post('/contracts/{contract}/payments', [LoanWorkspaceController::class, 'pay'])->whereNumber('contract')->name('payments.store');
            Route::post('/contracts/{contract}/insurance', [LoanInsuranceController::class, 'storePolicy'])->whereNumber('contract')->name('insurance.store');
            Route::post('/insurance/{policy}/premiums', [LoanInsuranceController::class, 'storePremium'])->whereNumber('policy')->name('premiums.store');
            Route::post('/premiums/{premium}/payments', [LoanInsuranceController::class, 'payPremium'])->whereNumber('premium')->name('premiums.pay');
        });
}
