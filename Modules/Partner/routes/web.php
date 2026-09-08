<?php

use Illuminate\Support\Facades\Route;
use Modules\Partner\Http\Controllers\PartnerController;

Route::prefix('admin/partners')
    ->name('admin.partners.')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {
        Route::view('/dashboard', 'partner::pages.dashboard')->name('dashboard');
        Route::view('/business-lookup', 'partner::pages.business-lookup')->name('lookup');
        Route::view('/sync/invoices', 'partner::pages.invoice-candidates')->name('invoice-candidates');
        Route::get('/', [PartnerController::class, 'index'])->name('index');
        Route::get('/create', [PartnerController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [PartnerController::class, 'edit'])->name('edit');
    });

// Legacy Partner routes remain available while callers migrate to the canonical /admin/partners workspace.
Route::prefix('admin/partner')
    ->name('admin.partner.')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [PartnerController::class, 'index'])->name('index');
            Route::get('/create', [PartnerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [PartnerController::class, 'edit'])->name('edit');
        });
    });
