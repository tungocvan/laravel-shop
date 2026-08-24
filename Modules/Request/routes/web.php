<?php

use Illuminate\Support\Facades\Route;
use Modules\Request\Http\Controllers\RequestAttachmentController;
use Modules\Request\Http\Controllers\RequestDashboardController;
use Modules\Request\Http\Controllers\RequestDefinitionController;
use Modules\Request\Http\Controllers\RequestDefinitionPackageController;
use Modules\Request\Http\Controllers\RequestExportController;
use Modules\Request\Http\Controllers\RequestOperationsController;
use Modules\Request\Http\Controllers\RequestReportController;
use Modules\Request\Http\Controllers\RequestRequesterController;
use Modules\Request\Http\Middleware\UseVietnameseRequestLocale;

Route::middleware(['web', UseVietnameseRequestLocale::class, 'auth:admin'])->prefix('admin/requests/admin')->name('request.admin.')->group(function (): void {
    Route::get('/groups', [RequestDefinitionController::class, 'groups'])->middleware('permission:request.group.view,admin')->name('groups');
    Route::get('/types', [RequestDefinitionController::class, 'types'])->middleware('permission:request.type.view,admin')->name('types');
    Route::get('/types/{typePublicId}/package', [RequestDefinitionPackageController::class, 'show'])->whereUlid('typePublicId')->middleware('permission:request.type.view,admin')->name('types.package');
    Route::get('/types/{typePublicId}/package/download', [RequestDefinitionPackageController::class, 'download'])->whereUlid('typePublicId')->middleware(['permission:request.type.export,admin', 'throttle:request-download'])->name('types.package.download');
    Route::post('/types/{typePublicId}/package/preview', [RequestDefinitionPackageController::class, 'preview'])->whereUlid('typePublicId')->middleware(['permission:request.type.import,admin', 'throttle:request-upload'])->name('types.package.preview');
    Route::post('/types/{typePublicId}/package/import', [RequestDefinitionPackageController::class, 'import'])->whereUlid('typePublicId')->middleware(['permission:request.type.import,admin', 'throttle:request-upload'])->name('types.package.import');
    Route::get('/types/{typePublicId}/designer', [RequestDefinitionController::class, 'designer'])->whereUlid('typePublicId')->middleware('permission:request.type.update,admin')->name('types.designer');
    Route::get('/types/{typePublicId}/versions', [RequestDefinitionController::class, 'versions'])->whereUlid('typePublicId')->middleware('permission:request.type.view,admin')->name('types.versions');
    Route::get('/reports', RequestReportController::class)->middleware('permission:request.report.view,admin')->name('reports');
    Route::post('/reports/exports', [RequestExportController::class, 'store'])->middleware(['permission:request.export,admin', 'throttle:request-export'])->name('reports.exports.store');
    Route::get('/operations', [RequestOperationsController::class, 'index'])->middleware('permission:request.operation.view,admin')->name('operations');
    Route::post('/operations/retry', [RequestOperationsController::class, 'retry'])->middleware(['permission:request.operation.retry,admin', 'throttle:request-decide'])->name('operations.retry');
});

Route::middleware(['web', UseVietnameseRequestLocale::class, 'auth:admin'])->prefix('admin/requests')->name('request.')->group(function (): void {
    Route::get('/', RequestDashboardController::class)->middleware('permission:request.dashboard.view,admin')->name('dashboard');
    Route::get('/catalog', [RequestRequesterController::class, 'catalog'])->middleware('permission:request.instance.create,admin')->name('catalog');
    Route::get('/create/{typePublicId}', [RequestRequesterController::class, 'create'])->whereUlid('typePublicId')->middleware('permission:request.instance.create,admin')->name('create');
    Route::get('/mine', [RequestRequesterController::class, 'mine'])->middleware('permission:request.instance.view-own,admin')->name('mine');
    Route::get('/inbox', [RequestRequesterController::class, 'inbox'])->middleware('permission:request.task.view,admin')->name('inbox');
    Route::get('/exports/{exportPublicId}', [RequestExportController::class, 'download'])->whereUlid('exportPublicId')->middleware(['permission:request.export,admin', 'throttle:request-download'])->name('exports.download');
    Route::post('/{requestPublicId}/exports/pdf', [RequestExportController::class, 'pdf'])->whereUlid('requestPublicId')->middleware(['permission:request.export,admin', 'throttle:request-export'])->name('exports.pdf');
    Route::get('/{requestPublicId}/attachments/{attachmentPublicId}', RequestAttachmentController::class)->whereUlid('requestPublicId')->whereUlid('attachmentPublicId')->middleware(['permission:request.attachment.download,admin', 'throttle:request-download'])->name('attachments.download');
    Route::get('/{requestPublicId}', [RequestRequesterController::class, 'show'])->whereUlid('requestPublicId')->middleware('permission:request.instance.view-own|request.instance.view-participant|request.instance.view-all,admin')->name('show');
});
