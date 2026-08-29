<?php

use Illuminate\Support\Facades\Route;
use Modules\Muasamcong\Http\Controllers\MuasamcongController;
use Modules\Muasamcong\Http\Controllers\MuasamcongDashboardController;
use Modules\Muasamcong\Http\Controllers\PricingSearchHistoryController;
use Modules\Muasamcong\Http\Controllers\PricingWishlistBulkController;
use Modules\Muasamcong\Http\Controllers\SyncedPricingBbgExportController;
use Modules\Muasamcong\Http\Controllers\SyncedPricingExportController;

Route::middleware(config('muasamcong.route_middleware', ['web', 'auth:admin']))->prefix('admin/muasamcong')->name('muasamcong.')->group(function () {
    Route::middleware(config('muasamcong.view_middleware', ['permission:view_muasamcong,admin']))->group(function () {
        Route::get('/dashboard', MuasamcongDashboardController::class)->name('dashboard');
        Route::get('/', [MuasamcongController::class, 'index'])->name('index');
        Route::post('/pricing/export-selected', [MuasamcongController::class, 'exportSelectedPricing'])->name('pricing.export-selected');
        Route::delete('/pricing/history/item', [PricingSearchHistoryController::class, 'destroy'])->name('pricing.history.destroy');
        Route::delete('/pricing/history', [PricingSearchHistoryController::class, 'clear'])->name('pricing.history.clear');
        Route::get('/contractors', [MuasamcongController::class, 'contractors'])->name('contractors');
        Route::get('/contractors/history', [MuasamcongController::class, 'contractorSearches'])->name('contractors.history');
        Route::get('/contractors/history/{contractorSearch}', [MuasamcongController::class, 'contractorSearchDetail'])->name('contractors.history.show');
        Route::get('/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots', [MuasamcongController::class, 'manualContractorLots'])->name('contractors.manual-lots.show');
        Route::get('/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download', [MuasamcongController::class, 'downloadManualContractorLots'])->name('contractors.manual-lots.download');
        Route::get('/hsmt', [MuasamcongController::class, 'hsmt'])->name('hsmt');
        Route::get('/synced', [MuasamcongController::class, 'synced'])->name('synced');
        Route::post('/synced/export-selected', SyncedPricingExportController::class)->name('synced.export-selected');
        Route::post('/synced/export-bbg', SyncedPricingBbgExportController::class)->name('synced.export-bbg');
        Route::get('/wishlist', [MuasamcongController::class, 'wishlist'])->name('wishlist');
        Route::post('/wishlist/export-selected', [PricingWishlistBulkController::class, 'export'])->name('wishlist.export-selected');
        Route::delete('/wishlist/selected', [PricingWishlistBulkController::class, 'destroy'])->name('wishlist.destroy-selected');
    });
    Route::middleware(config('muasamcong.config_middleware', ['permission:muasamcong.config.manage,admin']))->group(function () {
        Route::get('/config', [MuasamcongController::class, 'config'])->name('config');
        Route::get('/session-tool/windows', [MuasamcongController::class, 'downloadWindowsSessionTool'])->name('session-tool.windows');
    });
});
