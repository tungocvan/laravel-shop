<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceWorkspaceController;
use Modules\Finance\Http\Controllers\FinanceVoucherPrintController;
use Modules\Finance\Http\Controllers\FinancePrintSettingsController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->group(function (): void {
        Route::get('/', FinanceWorkspaceController::class)
            ->middleware('permission:finance.view,admin')
            ->name('index');
        Route::get('/vouchers/{voucher}/print', FinanceVoucherPrintController::class)
            ->whereNumber('voucher')
            ->middleware('permission:finance.view,admin')
            ->name('vouchers.print');
        Route::get('/vouchers/{voucher}/pdf', [FinanceVoucherPrintController::class, 'pdf'])
            ->whereNumber('voucher')
            ->middleware('permission:finance.view,admin')
            ->name('vouchers.pdf');
        Route::get('/print-settings', [FinancePrintSettingsController::class, 'index'])
            ->middleware('permission:finance.account.manage,admin')
            ->name('print.settings');
        Route::post('/print-settings', [FinancePrintSettingsController::class, 'update'])
            ->middleware('permission:finance.account.manage,admin')
            ->name('print.settings.update');
    });
