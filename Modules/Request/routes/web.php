<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\RequestAttachmentController;
use Modules\Request\Http\Controllers\RequestDashboardController;
use Modules\Request\Http\Controllers\RequestDefinitionController;
use Modules\Request\Http\Controllers\RequestRequesterController;
use Modules\Request\Http\Middleware\UseVietnameseRequestLocale;

Route::middleware(['web', UseVietnameseRequestLocale::class, 'auth:admin'])->prefix('admin/requests/admin')->name('request.admin.')->group(function (): void {
    Route::get('/groups', [RequestDefinitionController::class, 'groups'])->middleware('permission:request.group.view,admin')->name('groups');
    Route::get('/types', [RequestDefinitionController::class, 'types'])->middleware('permission:request.type.view,admin')->name('types');
    Route::get('/types/{typePublicId}/designer', [RequestDefinitionController::class, 'designer'])->whereUlid('typePublicId')->middleware('permission:request.type.update,admin')->name('types.designer');
    Route::get('/types/{typePublicId}/versions', [RequestDefinitionController::class, 'versions'])->whereUlid('typePublicId')->middleware('permission:request.type.view,admin')->name('types.versions');
});

Route::middleware(['web', UseVietnameseRequestLocale::class, 'auth:admin'])->prefix('admin/requests')->name('request.')->group(function (): void {
    Route::get('/', RequestDashboardController::class)->middleware('permission:request.dashboard.view,admin')->name('dashboard');
    Route::get('/catalog', [RequestRequesterController::class, 'catalog'])->middleware('permission:request.instance.create,admin')->name('catalog');
    Route::get('/create/{typePublicId}', [RequestRequesterController::class, 'create'])->whereUlid('typePublicId')->middleware('permission:request.instance.create,admin')->name('create');
    Route::get('/mine', [RequestRequesterController::class, 'mine'])->middleware('permission:request.instance.view-own,admin')->name('mine');
    Route::get('/inbox', [RequestRequesterController::class, 'inbox'])->middleware('permission:request.task.view,admin')->name('inbox');
    Route::get('/{requestPublicId}/attachments/{attachmentPublicId}', RequestAttachmentController::class)->whereUlid('requestPublicId')->whereUlid('attachmentPublicId')->middleware(['permission:request.attachment.download,admin', 'throttle:request-download'])->name('attachments.download');
    Route::get('/{requestPublicId}', [RequestRequesterController::class, 'show'])->whereUlid('requestPublicId')->middleware('permission:request.instance.view-own|request.instance.view-participant|request.instance.view-all,admin')->name('show');
});
