<?php

use Illuminate\Support\Facades\Route;
use Modules\Admission\Http\Controllers\AdmissionController;

Route::middleware(['web'])
    ->prefix('/admission')
    ->name('admission.')
    ->group(function () {
        Route::get('/search/{ma_dinh_danh?}/{password?}', [AdmissionController::class, 'search'])->name('search');
    });

Route::middleware(['web', 'auth:admin'])
    ->prefix('/admin/admission')
    ->name('admin.admission.')
    ->group(function () {
        Route::get('/dashboard', [AdmissionController::class, 'dashboard'])
            ->middleware('permission:view_admission,admin')
            ->name('dashboard');

        Route::get('/', [AdmissionController::class, 'adminIndex'])
            ->middleware('permission:view_admission,admin')
            ->name('index');

        Route::view('/settings', 'Admission::pages.admin.settings')
            ->middleware('permission:manage_admission_settings,admin')
            ->name('settings.edit');

        Route::get('/create', [AdmissionController::class, 'adminCreate'])
            ->middleware('permission:create_admission,admin')
            ->name('create');

        Route::get('/edit/{id}', [AdmissionController::class, 'adminEdit'])
            ->middleware('permission:edit_admission,admin')
            ->name('edit');

        Route::get('/export-pdf/{id}', [AdmissionController::class, 'downloadPdf'])
            ->middleware('permission:download_admission_documents,admin')
            ->name('export-pdf');

        Route::get('/export', [AdmissionController::class, 'export'])
            ->middleware('permission:export_admission,admin')
            ->name('export');

        Route::post('/import', [AdmissionController::class, 'import'])
            ->middleware('permission:import_admission,admin')
            ->name('import');

        Route::get('/dvhc', [AdmissionController::class, 'dvhc'])
            ->middleware('permission:manage_admission_locations,admin')
            ->name('dvhc');

        Route::get('/list-class', [AdmissionController::class, 'listClass'])
            ->middleware('permission:view_admission,admin')
            ->name('list-class');
    });

Route::middleware(['web', 'auth:admin'])
    ->prefix('/admission')
    ->name('admission.')
    ->group(function () {
        Route::get('/register', [AdmissionController::class, 'index'])
            ->middleware('permission:create_admission,admin')
            ->name('register');

        Route::get('/download-pdf/{id}', [AdmissionController::class, 'downloadPdf'])
            ->middleware('permission:download_admission_documents,admin')
            ->name('download-pdf');

        Route::get('/download-word/{id}', [AdmissionController::class, 'downloadDocx'])
            ->middleware('permission:download_admission_documents,admin')
            ->name('download-word');

        Route::get('/{id}/download/{type}', [AdmissionController::class, 'download'])
            ->middleware('permission:download_admission_documents,admin')
            ->name('download');

        Route::get('/{id}/receipt', [AdmissionController::class, 'receipt'])
            ->middleware('permission:download_admission_documents,admin')
            ->name('receipt');
    });
