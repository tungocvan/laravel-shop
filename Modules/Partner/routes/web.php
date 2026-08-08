<?php

use Illuminate\Support\Facades\Route;
use Modules\Partner\Http\Controllers\PartnerController;

Route::prefix('admin/partner')
    ->name('admin.partner.')
    ->middleware(['web', 'auth:admin'])
    ->group(function () {

        // Khối Quản lý Đối tác
        Route::prefix('partners')->name('partners.')->group(function () {
            Route::get('/', [PartnerController::class, 'index'])->name('index');
            Route::get('/create', [PartnerController::class, 'create'])->name('create');
            Route::get('/{id}/edit', [PartnerController::class, 'edit'])->name('edit');
        });

    });
