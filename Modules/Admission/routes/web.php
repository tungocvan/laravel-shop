<?php

use Illuminate\Support\Facades\Route;
use Modules\Admission\Http\Controllers\AdmissionController;

Route::middleware(['web'])
    ->prefix('/admission')
    ->name('admission.')
    ->group(function () {
        Route::get('/search/{ma_dinh_danh?}/{password?}', [AdmissionController::class, 'search'])->name('search');

    });


Route::middleware(['web', 'auth:admin'])->group(function () {
    //Route::get('/', [AdmissionController::class, 'adminIndex'])->name('admin.dashboard');
    //Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/', [AdmissionController::class, 'dashboard']);
    Route::get('/admin', [AdmissionController::class, 'dashboard'])->name('admin.dashboard');
});

Route::middleware(['web', 'auth:admin'])
    ->prefix('/admin/admission')
    ->name('admin.admission.')
    ->group(function () {
        Route::get('/', [AdmissionController::class, 'adminIndex'])->name('index');
        Route::view('/settings', 'Admission::pages.admin.settings')
            ->middleware('permission:manage_admission_settings,admin')
            ->name('settings.edit');
        Route::get('/create', [AdmissionController::class, 'adminCreate'])->name('create');
        Route::get('/edit/{id}', [AdmissionController::class, 'adminEdit'])->name('edit');
        // Admin cũng có thể xuất PDF để lưu trữ hồ sơ
        Route::get('/export-pdf/{id}', [AdmissionController::class, 'downloadPdf'])->name('export-pdf');
        Route::get('/export', [AdmissionController::class, 'export'])->name('export');
        Route::post('/import', [AdmissionController::class, 'import'])->name('import');
        Route::get('/dvhc', [AdmissionController::class, 'dvhc'])
            ->middleware('permission:manage_admission_locations,admin')
            ->name('dvhc');
        Route::get('/list-class', [AdmissionController::class, 'listClass'])->name('list-class');
    });

Route::middleware(['web', 'auth:admin'])
    ->prefix('/admission')
    ->name('admission.')
    ->group(function () {
        Route::get('/register', [AdmissionController::class, 'index'])->name('register');

        Route::get('/download-pdf/{id}', [AdmissionController::class, 'downloadPdf'])->name('download-pdf');
        Route::get('/download-word/{id}', [AdmissionController::class, 'downloadDocx'])->name('download-word');
        Route::get('/{id}/download/{type}', [AdmissionController::class, 'download'])
            ->name('download');
        Route::get('/{id}/receipt', [AdmissionController::class, 'receipt'])
            ->name('receipt');
    });
