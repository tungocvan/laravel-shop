<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\RequestDefinitionController;
use Modules\Request\Http\Controllers\RequestRequesterController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/requests/admin')->name('request.admin.')->group(function (): void {
    Route::get('/groups', [RequestDefinitionController::class, 'groups'])->middleware('permission:request.group.view,admin')->name('groups');
    Route::get('/types', [RequestDefinitionController::class, 'types'])->middleware('permission:request.type.view,admin')->name('types');
    Route::get('/types/{typePublicId}/designer', [RequestDefinitionController::class, 'designer'])->whereUlid('typePublicId')->middleware('permission:request.type.update,admin')->name('types.designer');
    Route::get('/types/{typePublicId}/versions', [RequestDefinitionController::class, 'versions'])->whereUlid('typePublicId')->middleware('permission:request.type.view,admin')->name('types.versions');
});

Route::middleware(['web', 'auth:admin'])->prefix('admin/requests')->name('request.')->group(function (): void {
    Route::get('/catalog', [RequestRequesterController::class, 'catalog'])->middleware('permission:request.instance.create,admin')->name('catalog');
    Route::get('/create/{typePublicId}', [RequestRequesterController::class, 'create'])->whereUlid('typePublicId')->middleware('permission:request.instance.create,admin')->name('create');
    Route::get('/mine', [RequestRequesterController::class, 'mine'])->middleware('permission:request.instance.view-own,admin')->name('mine');
    Route::get('/{requestPublicId}', [RequestRequesterController::class, 'show'])->whereUlid('requestPublicId')->middleware('permission:request.instance.view-own,admin')->name('show');
});
