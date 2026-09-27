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
