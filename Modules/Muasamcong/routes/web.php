<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\ContractorKqlcntRecoveryController;
use Modules\Muasamcong\Http\Controllers\MuasamcongController;
use Modules\Muasamcong\Http\Controllers\MuasamcongDashboardController;
use Modules\Muasamcong\Http\Controllers\PricingExportController;
use Modules\Muasamcong\Http\Controllers\PricingSearchHistoryController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistBulkController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistExportController;
use Modules\Muasamcong\Http\Controllers\SyncedPricingBbgExportController;
use Modules\Muasamcong\Http\Controllers\SyncedPricingScopedExportController;

Route::middleware(config('muasamcong.route_middleware', ['web', 'auth:admin']))->prefix('admin/muasamcong')->name('muasamcong.')->group(function () {
    Route::middleware(config('muasamcong.view_middleware', ['permission:view_muasamcong,admin']))->group(function () {
        Route::get('/dashboard', MuasamcongDashboardController::class)->name('dashboard');
        Route::get('/', [MuasamcongController::class, 'index'])->name('index');
        Route::post('/pricing/export-selected', PricingExportController::class)->name('pricing.export-selected');
        Route::delete('/pricing/history/item', [PricingSearchHistoryController::class, 'destroy'])->name('pricing.history.destroy');
        Route::delete('/pricing/history', [PricingSearchHistoryController::class, 'clear'])->name('pricing.history.clear');
        Route::get('/contractors', [MuasamcongController::class, 'contractors'])->name('contractors');
        Route::get('/contractors/history', [MuasamcongController::class, 'contractorSearches'])->name('contractors.history');
        Route::get('/contractors/history/{contractorSearch}', [MuasamcongController::class, 'contractorSearchDetail'])->name('contractors.history.show');
        Route::get('/contractors/history/{contractorSearch}/kqlcnt-recovery', [ContractorKqlcntRecoveryController::class, 'index'])->name('contractors.kqlcnt-recovery');
        Route::get('/contractors/history/{contractorSearch}/kqlcnt-recovery/template', [ContractorKqlcntRecoveryController::class, 'template'])->name('contractors.kqlcnt-recovery.template');
        Route::post('/contractors/history/{contractorSearch}/kqlcnt-recovery/export', [ContractorKqlcntRecoveryController::class, 'export'])->name('contractors.kqlcnt-recovery.export');
        Route::get('/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots', [MuasamcongController::class, 'manualContractorLots'])->name('contractors.manual-lots.show');
        Route::get('/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download', [MuasamcongController::class, 'downloadManualContractorLots'])->name('contractors.manual-lots.download');
        Route::get('/hsmt', [MuasamcongController::class, 'hsmt'])->name('hsmt');
        Route::get('/synced', [MuasamcongController::class, 'synced'])->name('synced');
        Route::post('/synced/export-selected', SyncedPricingScopedExportController::class)->name('synced.export-selected');
        Route::post('/synced/export-bbg', SyncedPricingBbgExportController::class)->name('synced.export-bbg');
        Route::get('/wishlist', PricingWishlistController::class)->name('wishlist');
        Route::post('/wishlist/export-selected', PricingWishlistExportController::class)->name('wishlist.export-selected');
        Route::delete('/wishlist/selected', [PricingWishlistBulkController::class, 'destroy'])
            ->middleware('permission:muasamcong.pricing.wishlist,admin')
            ->name('wishlist.destroy-selected');
    });

    Route::middleware([
        'permission:view_muasamcong,admin',
        'permission:muasamcong.pricing.sync,admin',
    ])->group(function () {
        Route::post('/contractors/history/{contractorSearch}/kqlcnt-recovery/upload', [ContractorKqlcntRecoveryController::class, 'upload'])->name('contractors.kqlcnt-recovery.upload');
        Route::get('/contractors/history/{contractorSearch}/kqlcnt-recovery/{batch}', [ContractorKqlcntRecoveryController::class, 'batch'])->name('contractors.kqlcnt-recovery.batch');
        Route::post('/contractors/history/{contractorSearch}/kqlcnt-recovery/{batch}/preview', [ContractorKqlcntRecoveryController::class, 'preview'])->name('contractors.kqlcnt-recovery.preview');
        Route::post('/contractors/history/{contractorSearch}/kqlcnt-recovery/{batch}/confirm', [ContractorKqlcntRecoveryController::class, 'confirm'])->name('contractors.kqlcnt-recovery.confirm');
    });

    Route::middleware(config('muasamcong.config_middleware', ['permission:muasamcong.config.manage,admin']))->group(function () {
        Route::get('/config', [MuasamcongController::class, 'config'])->name('config');
        Route::get('/session-tool/windows', [MuasamcongController::class, 'downloadWindowsSessionTool'])->name('session-tool.windows');
    });
});
