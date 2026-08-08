<?php

use Illuminate\Support\Facades\Route;
use Modules\Invoices\Http\Controllers\InvoicesController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/invoices')->name('admin.invoices.')->group(function () {
    Route::get('/', [InvoicesController::class, 'index'])->middleware('permission:invoices-list')->name('index');
    Route::get('/create-token', [InvoicesController::class, 'createToken'])->middleware('permission:invoices-create')->name('create-token');
    Route::get('/hoadon', [InvoicesController::class, 'hoadon'])->middleware('permission:invoices-create')->name('hoadon');
    Route::get('/hoadon-list', [InvoicesController::class, 'hoadonList'])->middleware('permission:invoices-list')->name('hoadon-list');
    Route::get('download/{lookup_code}', [InvoicesController::class, 'download'])->middleware('permission:invoices-list')->name('download');
});

// Backward-compatible aliases for existing bookmarks.
Route::middleware(['web', 'auth:admin'])->prefix('invoices')->name('invoices.')->group(function () {
    Route::redirect('/', '/admin/invoices')->name('index');
    Route::redirect('/create-token', '/admin/invoices/create-token')->name('create-token');
    Route::redirect('/hoadon', '/admin/invoices/hoadon')->name('hoadon');
    Route::redirect('/hoadon-list', '/admin/invoices/hoadon-list')->name('hoadon-list');
    Route::get('/download/{lookup_code}', [InvoicesController::class, 'download'])
        ->middleware('permission:invoices-list')
        ->name('download');
});
