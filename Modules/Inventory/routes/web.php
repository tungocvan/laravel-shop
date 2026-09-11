<?php

use Illuminate\Support\Facades\Route;
use Modules\Inventory\Http\Controllers\InventoryAdminController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/inventory')->name('admin.inventory.')->group(function (): void {
    Route::get('/', [InventoryAdminController::class, 'dashboard'])->middleware('permission:inventory.dashboard.view')->name('dashboard');
    Route::get('/intake', [InventoryAdminController::class, 'intake'])->middleware('permission:inventory.receipt.view')->name('intake');
    Route::get('/invoice-inbox', [InventoryAdminController::class, 'invoiceInbox'])->middleware('permission:inventory.receipt.view')->name('invoice-inbox');
    Route::post('/invoice-inbox/{inboxId}/refresh-source', [InventoryAdminController::class, 'refreshInvoiceInboxSource'])->middleware('permission:inventory.receipt.manage')->name('invoice-inbox.refresh-source');
    Route::get('/warehouses', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'warehouses')->middleware('permission:inventory.warehouse.view')->name('warehouses');
    Route::get('/items', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'items')->middleware('permission:inventory.item.view')->name('items');
    Route::get('/receipts', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'receipts')->middleware('permission:inventory.receipt.view')->name('receipts');
    Route::get('/issues', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'issues')->middleware('permission:inventory.issue.view')->name('issues');
    Route::get('/transfers', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'transfers')->middleware('permission:inventory.transfer.view')->name('transfers');
    Route::get('/stocktakes', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'stocktakes')->middleware('permission:inventory.stocktake.view')->name('stocktakes');
    Route::get('/stock', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'stock')->middleware('permission:inventory.stock.view')->name('stock');
    Route::get('/lots', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'lots')->middleware('permission:inventory.stock.view')->name('lots');
    Route::get('/movements', [InventoryAdminController::class, 'workspace'])->defaults('workspace', 'movements')->middleware('permission:inventory.movement.view')->name('movements');
});
