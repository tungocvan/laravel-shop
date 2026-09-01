<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceDashboardController;
use Modules\Attendance\Http\Controllers\AttendanceDemoOperationsController;
use Modules\Attendance\Http\Controllers\AttendanceLocationsController;
use Modules\Attendance\Http\Controllers\AttendanceRecordsController;
use Modules\Attendance\Http\Controllers\AttendanceShiftsController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/attendance')->name('admin.attendance.')->group(function () {
    Route::get('/dashboard', AttendanceDashboardController::class)
        ->middleware('permission:attendance.dashboard.view,admin')
        ->name('dashboard');

    Route::get('/records', AttendanceRecordsController::class)
        ->middleware('permission:attendance.record.view,admin')
        ->name('records');

    Route::middleware('permission:attendance.location.view,admin')->group(function () {
        Route::get('/locations', [AttendanceLocationsController::class, 'index'])->name('locations.index');
    });
    Route::middleware('permission:attendance.location.manage,admin')->group(function () {
        Route::post('/locations/geocode', [AttendanceLocationsController::class, 'geocode'])->name('locations.geocode');
        Route::post('/locations', [AttendanceLocationsController::class, 'store'])->name('locations.store');
        Route::put('/locations/{location}', [AttendanceLocationsController::class, 'update'])->name('locations.update');
    });

    Route::middleware('permission:attendance.shift.view,admin')->group(function () {
        Route::get('/shifts', [AttendanceShiftsController::class, 'index'])->name('shifts.index');
    });
    Route::middleware('permission:attendance.shift.manage,admin')->group(function () {
        Route::post('/shifts', [AttendanceShiftsController::class, 'store'])->name('shifts.store');
        Route::put('/shifts/{shift}', [AttendanceShiftsController::class, 'update'])->name('shifts.update');
    });

    Route::middleware('permission:attendance.dashboard.view,admin')->group(function () {
        Route::get('/demo-operations', [AttendanceDemoOperationsController::class, 'index'])->name('demo.index');
        Route::post('/demo-operations/seed', [AttendanceDemoOperationsController::class, 'seed'])->name('demo.seed');
        Route::delete('/demo-operations/reset', [AttendanceDemoOperationsController::class, 'reset'])->name('demo.reset');
    });
});
