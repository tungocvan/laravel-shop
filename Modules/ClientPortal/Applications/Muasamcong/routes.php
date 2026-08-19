<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Muasamcong\Http\Controllers\MuasamcongApplicationController;

if ((bool) config('modules.registry.Muasamcong.enabled', false)) {
    Route::middleware(['web', 'auth:web', 'client.application:muasamcong'])
        ->prefix('apps/muasamcong')
        ->name('client.muasamcong.')
        ->group(function () {
            Route::get('/', [MuasamcongApplicationController::class, 'dashboard'])->name('dashboard');

            Route::middleware('client.feature:muasamcong,drug-pricing')->group(function () {
                Route::get('/drug-pricing', [MuasamcongApplicationController::class, 'drugPricing'])->name('drug-pricing');
                Route::get('/drug-pricing/{sourceId}', [MuasamcongApplicationController::class, 'drugPricingDetail'])
                    ->whereUuid('sourceId')
                    ->name('drug-pricing.detail');
                Route::post('/drug-pricing/sync', [MuasamcongApplicationController::class, 'queueDrugPricingSync'])->name('drug-pricing.sync');
            });
        });
}
