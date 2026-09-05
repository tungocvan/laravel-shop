<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\DrugBidAwardAllocationExportController;
use Modules\Pharma\Http\Controllers\DrugBidAwardContractExportController;
use Modules\Pharma\Http\Controllers\MedicineController;
use Modules\Pharma\Http\Controllers\OfficialFacilityImportController;
use Modules\Pharma\Http\Controllers\OfficialFacilityImportTemplateController;
use Modules\Pharma\Http\Controllers\PharmaDashboardController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/pharma')->name('admin.pharma.')->group(function () {
    Route::get('/', PharmaDashboardController::class)->name('dashboard');

    Route::middleware('permission:view_pharma_official_facilities')->group(function () {
        Route::get('/official-facilities/import', [OfficialFacilityImportController::class, 'index'])->name('official-facilities.index');
        Route::get('/official-facilities/import/template', OfficialFacilityImportTemplateController::class)->name('official-facilities.template');
    });

    Route::middleware('permission:import_pharma_official_facilities')->group(function () {
        Route::post('/official-facilities/import', [OfficialFacilityImportController::class, 'store'])->name('official-facilities.store');
        Route::put('/official-facilities/import/{batch}/selection', [OfficialFacilityImportController::class, 'selection'])->name('official-facilities.selection');
        Route::post('/official-facilities/import/{batch}/run', [OfficialFacilityImportController::class, 'importSelected'])->name('official-facilities.run');
    });

    Route::put('/official-facilities/import/rows/{row}/resolve', [OfficialFacilityImportController::class, 'resolve'])
        ->middleware('permission:resolve_pharma_official_facility_conflicts')
        ->name('official-facilities.resolve');

    require __DIR__.'/web_legacy.php';
});
