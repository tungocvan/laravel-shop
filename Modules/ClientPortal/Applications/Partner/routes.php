<?php
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Partner\Http\Controllers\PartnerApplicationController;
if ((bool) config('modules.registry.Partner.enabled', false)) {
 Route::middleware(['web','auth:web','client.application:partners'])->prefix('apps/partners')->name('client.partners.')->group(function () {
  Route::get('/', [PartnerApplicationController::class,'index'])->middleware('client.feature:partners,list')->name('index');
  Route::get('/list', [PartnerApplicationController::class,'index'])->middleware('client.feature:partners,list')->name('list');
  Route::post('/import', [PartnerApplicationController::class,'import'])->middleware('permission:client.partners.create,web')->name('import');
  Route::get('/export', [PartnerApplicationController::class,'export'])->middleware('permission:client.partners.view,web')->name('export');
  Route::get('/template', [PartnerApplicationController::class,'template'])->middleware('permission:client.partners.create,web')->name('template');
  Route::get('/create', [PartnerApplicationController::class,'create'])->middleware('client.feature:partners,create')->name('create');
  Route::post('/', [PartnerApplicationController::class,'store'])->middleware('permission:client.partners.create,web')->name('store');
  Route::get('/{partner}/edit', [PartnerApplicationController::class,'edit'])->middleware('permission:client.partners.edit,web')->name('edit');
  Route::put('/{partner}', [PartnerApplicationController::class,'update'])->middleware('permission:client.partners.edit,web')->name('update');
  Route::delete('/{partner}', [PartnerApplicationController::class,'destroy'])->middleware('permission:client.partners.delete,web')->name('destroy');
 });
}