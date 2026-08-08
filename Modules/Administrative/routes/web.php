<?php

use Illuminate\Support\Facades\Route;
use Modules\Administrative\Http\Controllers\ProcedureController;
use Modules\Administrative\Http\Controllers\PublicLookupController;
use Modules\Administrative\Http\Controllers\PublicProcedureController;
use Modules\Administrative\Http\Controllers\SubmissionController;

Route::middleware(['web'])->prefix('tra-cuu-ho-so')->name('administrative.lookup.')->group(function (): void {
    Route::get('/', [PublicLookupController::class, 'index'])->name('index');
    Route::get('/{accessToken}', [PublicLookupController::class, 'show'])
        ->where('accessToken', '[a-f0-9]{64}')->middleware('cache.headers:no_store;private')->name('show');
    Route::get('/{accessToken}/files/{file}', [PublicLookupController::class, 'downloadResult'])
        ->where(['accessToken' => '[a-f0-9]{64}', 'file' => '[0-9]+'])
        ->middleware('throttle:30,1')->name('files.download');
});

Route::middleware(['web'])->group(function (): void {
    Route::prefix('thu-tuc-hanh-chinh')->name('administrative.public.')->group(function (): void {
        Route::get('/', [PublicProcedureController::class, 'index'])->name('index');
        Route::get('/nop-thanh-cong/{receipt}', [PublicProcedureController::class, 'success'])
            ->where('receipt', '[a-f0-9]{48}')
            ->middleware(['throttle:30,1', 'cache.headers:no_store;private'])->name('success');
        Route::get('/nop-thanh-cong/{receipt}/bien-nhan.pdf', [PublicProcedureController::class, 'downloadReceipt'])
            ->where('receipt', '[a-f0-9]{48}')
            ->middleware(['throttle:20,1', 'cache.headers:no_store;private'])->name('receipt.download');
        Route::get('/{procedure:slug}', [PublicProcedureController::class, 'show'])->name('show');
        Route::get('/{procedure:slug}/bieu-mau', [PublicProcedureController::class, 'downloadTemplate'])
            ->middleware('throttle:30,1')->name('template.download');
        Route::get('/{procedure:slug}/nop-ho-so', [PublicProcedureController::class, 'submit'])
            ->middleware('throttle:20,1')->name('submit');
    });
});

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/administrative')
    ->name('admin.administrative.')
    ->group(function (): void {
        Route::get('/', [SubmissionController::class, 'index'])
            ->middleware('permission:administrative.submission.view,admin')
            ->name('dashboard');
        Route::get('/submissions', [SubmissionController::class, 'index'])
            ->middleware('permission:administrative.submission.view,admin')
            ->name('submissions.index');
        Route::get('/submissions/{id}', [SubmissionController::class, 'show'])
            ->whereNumber('id')->middleware('permission:administrative.submission.view,admin')
            ->name('submissions.show');
        Route::get('/submissions/{submission}/files/{file}', [SubmissionController::class, 'downloadFile'])
            ->whereNumber(['submission', 'file'])->middleware('permission:administrative.file.download,admin')
            ->name('submissions.files.download');
    });

Route::middleware(['web', 'auth:admin'])
    ->prefix('admin/administrative/procedures')
    ->name('admin.administrative.procedures.')
    ->group(function (): void {
        Route::get('/', [ProcedureController::class, 'index'])
            ->middleware('permission:administrative.procedure.view,admin')
            ->name('index');
        Route::get('/create', [ProcedureController::class, 'create'])
            ->middleware('permission:administrative.procedure.create,admin')
            ->name('create');
        Route::get('/{id}/template', [ProcedureController::class, 'downloadTemplate'])
            ->whereNumber('id')
            ->middleware('permission:administrative.procedure.view,admin')
            ->name('template.download');
        Route::get('/{id}/edit', [ProcedureController::class, 'edit'])
            ->whereNumber('id')
            ->middleware('permission:administrative.procedure.update,admin')
            ->name('edit');
    });
