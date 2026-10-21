<?php

use Illuminate\Support\Facades\Route;
use Modules\Finance\Http\Controllers\FinanceWorkspaceController;

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/finance')
    ->name('admin.finance.')
    ->group(function (): void {
        Route::get('/', FinanceWorkspaceController::class)
            ->middleware('permission:finance.view,admin')
            ->name('index');
    });
