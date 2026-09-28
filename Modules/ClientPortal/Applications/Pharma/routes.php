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
        Route::get('/commercial', [PharmaApplicationController::class, 'commercial'])
            ->middleware('client.feature:pharma,commercial')
            ->name('commercial');
        Route::get('/commercial/hospitals/{hospital}', [PharmaApplicationController::class, 'commercialHospital'])
            ->whereNumber('hospital')
            ->middleware('client.feature:pharma,commercial')
            ->name('commercial.hospitals.show');
        Route::get('/price-lists', [PharmaApplicationController::class, 'priceLists'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists');
        Route::get('/price-lists/create', [PharmaApplicationController::class, 'createPriceList'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.create');
        Route::post('/price-lists', [PharmaApplicationController::class, 'storePriceList'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.store');
        Route::post('/price-lists/global', [PharmaApplicationController::class, 'storeGlobalPriceList'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.global.store');
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
        Route::put('/price-list-approvals/{priceList}/items/{item}', [PharmaApplicationController::class, 'updatePriceListApprovalItem'])
            ->whereNumber(['priceList', 'item'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.items.update');
        Route::delete('/price-list-approvals/{priceList}/items/{item}', [PharmaApplicationController::class, 'deletePriceListApprovalItem'])
            ->whereNumber(['priceList', 'item'])
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.items.delete');
        Route::post('/price-lists/{priceList}/activate-own-draft', [PharmaApplicationController::class, 'activateOwnDraftPriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.activate-own-draft');
        Route::post('/price-list-approvals/{priceList}/approve', [PharmaApplicationController::class, 'approvePriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.approve');
        Route::post('/price-list-approvals/{priceList}/reject', [PharmaApplicationController::class, 'rejectPriceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-list-approvals.reject');

        Route::post('/price-lists/{priceList}/deactivation-request', [PharmaApplicationController::class, 'requestPriceListDeactivation'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.deactivation.request');
        Route::post('/price-lists/{priceList}/deactivation-approve', [PharmaApplicationController::class, 'approvePriceListDeactivation'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.deactivation.approve');
        Route::post('/price-lists/{priceList}/deactivate', [PharmaApplicationController::class, 'deactivatePriceListDirectly'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.deactivate');

        Route::get('/price-lists/{priceList}', [PharmaApplicationController::class, 'priceList'])
            ->whereNumber('priceList')
            ->middleware('client.feature:pharma,price-lists')
            ->name('price-lists.show');

        Route::get('/products/{variant}', [PharmaApplicationController::class, 'product'])
            ->whereNumber('variant')
            ->middleware('client.feature:pharma,products')
            ->name('products.show');
        Route::post('/price-lists/{priceList}/export-share', [PharmaApplicationController::class, 'exportPriceListShare'])
            ->whereNumber('priceList')->middleware('client.feature:pharma,price-lists')->name('price-lists.export-share');
        Route::delete('/price-list-export-shares/{share}', [PharmaApplicationController::class, 'revokePriceListShare'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.revoke');
        Route::post('/price-list-export-shares/{share}/pdf', [PharmaApplicationController::class, 'queuePriceListSharePdf'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.pdf.queue');
        Route::post('/price-list-export-shares/{share}/pdf/regenerate', [PharmaApplicationController::class, 'regeneratePriceListSharePdf'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.pdf.regenerate');
        Route::delete('/price-list-export-shares/{share}/export', [PharmaApplicationController::class, 'deletePriceListExportShare'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.export.delete');
        Route::post('/price-list-export-shares/{share}/email', [PharmaApplicationController::class, 'emailPriceListExportShare'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.email');
        Route::get('/price-list-export-shares/{share}/status', [PharmaApplicationController::class, 'priceListShareStatus'])
            ->whereNumber('share')->middleware('client.feature:pharma,price-lists')->name('price-lists.share.status');
    });

    Route::middleware('web')->get('/share/pharma/price-lists/{token}', [PharmaApplicationController::class, 'downloadPriceListShare'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('client.pharma.price-lists.share.download');
    Route::middleware('web')->get('/share/pharma/price-lists/{token}/pdf', [PharmaApplicationController::class, 'downloadPriceListSharePdf'])
        ->where('token', '[A-Za-z0-9]{64}')
        ->name('client.pharma.price-lists.share.pdf');
}
