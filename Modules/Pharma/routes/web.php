<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\DrugBidAwardController;
use Modules\Pharma\Http\Controllers\OfficialFacilityImportController;
use Modules\Pharma\Http\Controllers\PharmaController;
use Modules\Pharma\Http\Controllers\PharmaDashboardController;
use Modules\Pharma\Http\Controllers\PriceListController;
use Modules\Pharma\Http\Controllers\SupplierTrackingController;

Route::prefix('admin/pharma')->name('admin.pharma.')->middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/', PharmaDashboardController::class)->middleware('can:view_pharma')->name('dashboard');

    Route::prefix('hssp')->name('hssp.')->group(function () {
        Route::get('/', [PharmaController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [PharmaController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/edit', [PharmaController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
    });

    Route::prefix('drug-bid-awards')->name('drug-bid-awards.')->group(function () {
        Route::get('/', [DrugBidAwardController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [DrugBidAwardController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/allocations', [DrugBidAwardController::class, 'allocations'])->middleware('can:view_pharma_allocations')->name('allocations');
        Route::get('/{id}/edit', [DrugBidAwardController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
    });

    Route::prefix('supplier-trackings')->name('supplier-trackings.')->group(function () {
        Route::get('/', [SupplierTrackingController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [SupplierTrackingController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/edit', [SupplierTrackingController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
    });

    Route::prefix('official-facilities')->name('official-facilities.')->group(function () {
        Route::get('/import', [OfficialFacilityImportController::class, 'index'])->middleware('can:view_pharma_official_facilities')->name('index');
        Route::post('/import', [OfficialFacilityImportController::class, 'store'])->middleware('can:import_pharma_official_facilities')->name('store');
        Route::put('/import/{batch}/selection', [OfficialFacilityImportController::class, 'selection'])->middleware('can:import_pharma_official_facilities')->name('selection');
        Route::post('/import/{batch}/run', [OfficialFacilityImportController::class, 'importSelected'])->middleware('can:import_pharma_official_facilities')->name('run');
        Route::put('/rows/{row}/resolve', [OfficialFacilityImportController::class, 'resolve'])->middleware('can:resolve_pharma_official_facility_conflicts')->name('resolve');
    });

    Route::get('/price-lists/create', [PriceListController::class, 'create'])->middleware('can:create_pharma')->name('price-lists.create');
});
