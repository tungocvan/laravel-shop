<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceWorkspaceController;
use Modules\Finance\Http\Controllers\FinanceVoucherPrintController;

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
    });
