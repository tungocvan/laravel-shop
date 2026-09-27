<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Pharma\Http\Controllers\PharmaApplicationController;

if ((bool) config('modules.registry.Pharma.enabled', false)) {
    Route::middleware([
        'web',
        'auth:web',
        'client.application:pharma',
    ])->prefix('apps/pharma')->name('client.pharma.')->group(function (): void {
        Route::get('/', [PharmaApplicationController::class, 'dashboard'])
            ->middleware('client.feature:pharma,overview')
            ->name('dashboard');

        Route::get('/products', [PharmaApplicationController::class, 'products'])
            ->middleware('client.feature:pharma,products')
            ->name('products');
        Route::get('/price-lists', [PharmaApplicationController::class, 'priceLists'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists');
        Route::get('/price-lists/create', [PharmaApplicationController::class, 'createPriceList'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.create');
        Route::post('/price-lists', [PharmaApplicationController::class, 'storePriceList'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.store');
        Route::get('/price-lists/{priceList}/edit', [PharmaApplicationController::class, 'editPriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.edit');
        Route::put('/price-lists/{priceList}', [PharmaApplicationController::class, 'updatePriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.update');
        Route::delete('/price-lists/{priceList}', [PharmaApplicationController::class, 'deletePriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.delete');

        Route::post('/price-lists/{priceList}/submit', [PharmaApplicationController::class, 'submitPriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.submit');

        Route::get('/price-list-approvals', [PharmaApplicationController::class, 'priceListApprovals'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals');
        Route::get('/price-list-approvals/{priceList}', [PharmaApplicationController::class, 'priceListApproval'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.show');
        Route::post('/price-list-approvals/{priceList}/approve', [PharmaApplicationController::class, 'approvePriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.approve');
        Route::post('/price-list-approvals/{priceList}/reject', [PharmaApplicationController::class, 'rejectPriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.reject');

        Route::get('/price-lists/{priceList}', [PharmaApplicationController::class, 'priceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.show');

        Route::get('/products/{variant}', [PharmaApplicationController::class, 'product'])
            ->whereNumber('variant')
            ->middleware('client.feature:pharma,products')
            ->name('products.show');
    });
}
