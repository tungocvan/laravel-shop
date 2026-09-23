<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\BhxhOfficialFacilityLookupController;
use Modules\Pharma\Http\Controllers\DrugBidAwardController;
use Modules\Pharma\Http\Controllers\HsspController;
use Modules\Pharma\Http\Controllers\InventoryController;
use Modules\Pharma\Http\Controllers\MedicineCatalogImportController;
use Modules\Pharma\Http\Controllers\OfficialFacilityImportController;
use Modules\Pharma\Http\Controllers\OfficialFacilityImportTemplateController;
use Modules\Pharma\Http\Controllers\OfficialSourceSyncController;
use Modules\Pharma\Http\Controllers\PharmaController;
use Modules\Pharma\Http\Controllers\PharmaDashboardController;
use Modules\Pharma\Http\Controllers\PriceListController;
use Modules\Pharma\Http\Controllers\SupplierTrackingController;

Route::prefix('admin/pharma')->name('admin.pharma.')->middleware(['web', 'auth:admin'])->group(function () {
    Route::get('/', PharmaDashboardController::class)->middleware('can:view_pharma')->name('dashboard');

    Route::middleware('can:view_pharma_official_facilities')->group(function () {
        Route::get('/official-facilities/import', [OfficialFacilityImportController::class, 'index'])->name('official-facilities.index');
        Route::get('/official-facilities/import/template', OfficialFacilityImportTemplateController::class)->name('official-facilities.template');
        Route::get('/official-facilities/source', [OfficialSourceSyncController::class, 'index'])->name('official-facilities.source.index');
        Route::post('/official-facilities/source/export', [OfficialSourceSyncController::class, 'export'])->name('official-facilities.source.export');
        Route::post('/official-facilities/source/import', [OfficialSourceSyncController::class, 'import'])->middleware('can:import_pharma_official_facilities')->name('official-facilities.source.import');
        Route::get('/official-facilities/source/sync/{batch}', [OfficialSourceSyncController::class, 'status'])->name('official-facilities.source.sync-status');
        Route::get('/official-facilities/bhxh', [BhxhOfficialFacilityLookupController::class, 'index'])->name('official-facilities.bhxh.index');
        Route::get('/official-facilities/bhxh/captcha', [BhxhOfficialFacilityLookupController::class, 'captcha'])->name('official-facilities.bhxh.captcha');
        Route::get('/official-facilities/bhxh/districts', [BhxhOfficialFacilityLookupController::class, 'districts'])->name('official-facilities.bhxh.districts');
        Route::get('/official-facilities/bhxh/cached', [BhxhOfficialFacilityLookupController::class, 'cached'])->name('official-facilities.bhxh.cached');
        Route::post('/official-facilities/bhxh/lookup', [BhxhOfficialFacilityLookupController::class, 'lookup'])->name('official-facilities.bhxh.lookup');
    });

    Route::middleware('can:sync_pharma_official_facilities')->group(function () {
        Route::post('/official-facilities/source/sync', [OfficialSourceSyncController::class, 'store'])->name('official-facilities.source.sync');
    });

    Route::middleware('can:import_pharma_official_facilities')->group(function () {
        Route::post('/official-facilities/import', [OfficialFacilityImportController::class, 'store'])->name('official-facilities.store');
        Route::put('/official-facilities/import/{batch}/selection', [OfficialFacilityImportController::class, 'selection'])->name('official-facilities.selection');
        Route::post('/official-facilities/import/{batch}/run', [OfficialFacilityImportController::class, 'importSelected'])->name('official-facilities.run');
    });

    Route::put('/official-facilities/import/rows/{row}/resolve', [OfficialFacilityImportController::class, 'resolve'])
        ->middleware('can:resolve_pharma_official_facility_conflicts')
        ->name('official-facilities.resolve');

    Route::prefix('medicines')->name('medicines.')->group(function () {
        Route::get('/', [PharmaController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [PharmaController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/edit', [PharmaController::class, 'edit'])->whereNumber('id')->middleware('can:edit_pharma')->name('edit');
        Route::get('/import/template', [MedicineCatalogImportController::class, 'template'])->middleware('can:view_pharma')->name('import.template');
        Route::get('/import', [MedicineCatalogImportController::class, 'index'])->middleware('can:view_pharma')->name('import.index');
        Route::post('/import', [MedicineCatalogImportController::class, 'store'])->middleware('can:edit_pharma')->name('import.store');
        Route::delete('/import/history', [MedicineCatalogImportController::class, 'clearHistory'])->middleware('can:edit_pharma')->name('import.history.clear');
        Route::put('/import/{batch}/selection', [MedicineCatalogImportController::class, 'selection'])->middleware('can:edit_pharma')->name('import.selection');
        Route::post('/import/{batch}/commit', [MedicineCatalogImportController::class, 'commit'])->middleware('can:edit_pharma')->name('import.commit');
    });

    Route::prefix('hssp')->name('hssp.')->group(function () {
        Route::get('/', [HsspController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/{medicine}/create', [HsspController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::post('/{medicine}', [HsspController::class, 'store'])->middleware('can:create_pharma')->name('store');
        Route::get('/{medicine}/{profile}/edit', [HsspController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
        Route::put('/{medicine}/{profile}', [HsspController::class, 'update'])->middleware('can:edit_pharma')->name('update');
        Route::delete('/{medicine}/{profile}', [HsspController::class, 'destroy'])->middleware('can:edit_pharma')->name('destroy');
    });

    Route::prefix('drug-bid-awards')->name('drug-bid-awards.')->group(function () {
        Route::get('/', [DrugBidAwardController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/review', [DrugBidAwardController::class, 'review'])->middleware('can:edit_pharma')->name('review');
        Route::get('/create', [DrugBidAwardController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/allocations', [DrugBidAwardController::class, 'allocations'])->middleware('can:view_pharma_allocations')->name('allocations');
        Route::get('/{id}/allocation-detail', [DrugBidAwardController::class, 'allocationDetail'])->middleware('can:view_pharma_allocations')->name('allocation-detail');
        Route::get('/{id}/commercial-policy', [DrugBidAwardController::class, 'commercialPolicy'])->middleware('can:view_pharma_commercial_policies')->name('commercial-policy');
        Route::get('/{id}/edit', [DrugBidAwardController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
    });

    Route::prefix('supplier-trackings')->name('supplier-trackings.')->group(function () {
        Route::get('/', [SupplierTrackingController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [SupplierTrackingController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::get('/{id}/edit', [SupplierTrackingController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
    });


    Route::prefix('inventory')->name('inventory.')->middleware('can:view_pharma')->group(function () {
        Route::get('/', [InventoryController::class, 'index'])->name('index');
        Route::get('/receipts/create', [InventoryController::class, 'createReceipt'])->middleware('can:create_pharma')->name('receipts.create');
        Route::post('/receipts', [InventoryController::class, 'storeReceipt'])->middleware('can:create_pharma')->name('receipts.store');
        Route::post('/receipts/{receipt}/post', [InventoryController::class, 'postReceipt'])->middleware('can:edit_pharma')->name('receipts.post');
        Route::get('/issues/create', [InventoryController::class, 'createIssue'])->middleware('can:create_pharma')->name('issues.create');
        Route::post('/issues', [InventoryController::class, 'storeIssue'])->middleware('can:create_pharma')->name('issues.store');
        Route::post('/issues/{issue}/post', [InventoryController::class, 'postIssue'])->middleware('can:edit_pharma')->name('issues.post');
    });

    Route::prefix('price-lists')->name('price-lists.')->group(function () {
        Route::get('/', [PriceListController::class, 'index'])->middleware('can:view_pharma')->name('index');
        Route::get('/create', [PriceListController::class, 'create'])->middleware('can:create_pharma')->name('create');
        Route::post('/', [PriceListController::class, 'store'])->middleware('can:create_pharma')->name('store');
        Route::get('/{priceList}', [PriceListController::class, 'show'])->middleware('can:view_pharma')->name('show');
        Route::get('/{priceList}/edit', [PriceListController::class, 'edit'])->middleware('can:edit_pharma')->name('edit');
        Route::match(['put', 'patch'], '/{priceList}', [PriceListController::class, 'update'])->middleware('can:edit_pharma')->name('update');
        Route::post('/{priceList}/activate', [PriceListController::class, 'activate'])->middleware('can:edit_pharma')->name('activate');
        Route::post('/{priceList}/deactivate', [PriceListController::class, 'deactivate'])->middleware('can:edit_pharma')->name('deactivate');
        Route::post('/{priceList}/clone', [PriceListController::class, 'clone'])->middleware('can:create_pharma')->name('clone');
        Route::get('/{priceList}/export', [PriceListController::class, 'export'])->middleware('can:view_pharma')->name('export');
    });
});
