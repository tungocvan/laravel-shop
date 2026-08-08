<?php

use Illuminate\Support\Facades\Route;
use Modules\Pharma\Http\Controllers\PharmaController;
use Modules\Pharma\Http\Controllers\DrugBidAwardController;
use Modules\Pharma\Http\Controllers\SupplierTrackingController;
use Modules\Pharma\Http\Controllers\PriceListController;

Route::prefix('admin/pharma')->name('admin.pharma.')->middleware(['web', 'auth:admin'])->group(function () {

    // Khối Quản lý Hồ sơ sản phẩm thuốc gốc
    Route::prefix('hssp')->name('hssp.')->group(function () {
        Route::get('/', [PharmaController::class, 'index'])->name('index');
        Route::get('/create', [PharmaController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [PharmaController::class, 'edit'])->name('edit');
    });

    // Khối Quản lý Hồ sơ thuốc trúng thầu mới bổ sung
    Route::prefix('drug-bid-awards')->name('drug-bid-awards.')->group(function () {
        Route::get('/', [DrugBidAwardController::class, 'index'])->name('index');
        Route::get('/create', [DrugBidAwardController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [DrugBidAwardController::class, 'edit'])->name('edit');
    });

    // Khối Quản lý Nhà cung cấp
    Route::prefix('supplier-trackings')->name('supplier-trackings.')->group(function () {
        Route::get('/', [SupplierTrackingController::class, 'index'])->name('index');
        Route::get('/create', [SupplierTrackingController::class, 'create'])->name('create');
        Route::get('/{id}/edit', [SupplierTrackingController::class, 'edit'])->name('edit');
        Route::get('/import-export', [SupplierTrackingController::class, 'importExport'])
            ->name('import-export');
    });

    // Tạo bảng giá từ workbook tổng hợp trong private storage.
    Route::get('/price-lists/create', [PriceListController::class, 'create'])
        ->name('price-lists.create');

});
