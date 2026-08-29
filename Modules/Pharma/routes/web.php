<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\DrugBidAwardController;
use Modules\Pharma\Http\Controllers\PharmaController;
use Modules\Pharma\Http\Controllers\PriceListController;
use Modules\Pharma\Http\Controllers\SupplierTrackingController;

Route::prefix('admin/pharma')->name('admin.pharma.')->middleware(['web', 'auth:admin'])->group(function () {
    Route::prefix('hssp')->name('hssp.')->group(function () {
        Route::get('/', [PharmaController::class, 'index'])
            ->middleware('can:view_pharma')
            ->name('index');
        Route::get('/create', [PharmaController::class, 'create'])
            ->middleware('can:create_pharma')
            ->name('create');
        Route::get('/{id}/edit', [PharmaController::class, 'edit'])
            ->middleware('can:edit_pharma')
            ->name('edit');
    });

    Route::prefix('drug-bid-awards')->name('drug-bid-awards.')->group(function () {
        Route::get('/', [DrugBidAwardController::class, 'index'])
            ->middleware('can:view_pharma')
            ->name('index');
        Route::get('/create', [DrugBidAwardController::class, 'create'])
            ->middleware('can:create_pharma')
            ->name('create');
        Route::get('/{id}/edit', [DrugBidAwardController::class, 'edit'])
            ->middleware('can:edit_pharma')
            ->name('edit');
    });

    Route::prefix('supplier-trackings')->name('supplier-trackings.')->group(function () {
        Route::get('/', [SupplierTrackingController::class, 'index'])
            ->middleware('can:view_pharma')
            ->name('index');
        Route::get('/create', [SupplierTrackingController::class, 'create'])
            ->middleware('can:create_pharma')
            ->name('create');
        Route::get('/{id}/edit', [SupplierTrackingController::class, 'edit'])
            ->middleware('can:edit_pharma')
            ->name('edit');
        Route::get('/import-export', [SupplierTrackingController::class, 'importExport'])
            ->middleware('can:edit_pharma')
            ->name('import-export');
    });

    Route::get('/price-lists/create', [PriceListController::class, 'create'])
        ->middleware('can:create_pharma')
        ->name('price-lists.create');
});
