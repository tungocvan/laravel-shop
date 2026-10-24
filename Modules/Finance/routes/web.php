<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceWorkspaceController;
use Modules\Finance\Http\Controllers\DebtWorkspaceController;
use Modules\Finance\Http\Controllers\FinanceVoucherPrintController;
use Modules\Finance\Http\Controllers\FinancePrintSettingsController;
use Modules\Finance\Http\Controllers\FinanceReportExportController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->group(function (): void {
        Route::get('/', FinanceWorkspaceController::class)
            ->middleware('permission:finance.view,admin')
            ->name('index');
        Route::get('/debts/{section?}', DebtWorkspaceController::class)
            ->whereIn('section', ['dashboard', 'partners', 'documents', 'ledger', 'summary', 'aging', 'settings'])
            ->middleware('permission:finance.view,admin')
            ->name('debts.index');
        Route::get('/reports/vouchers.xlsx', FinanceReportExportController::class)
            ->middleware('permission:finance.view,admin')
            ->name('reports.vouchers.excel');
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
