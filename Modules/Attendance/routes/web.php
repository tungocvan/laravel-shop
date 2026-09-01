<?php

use Illuminate\Support\Facades\Route;
use Modules\Attendance\Http\Controllers\AttendanceDashboardController;
use Modules\Attendance\Http\Controllers\AttendanceRecordsController;

Route::middleware(['web', 'auth:admin'])->prefix('admin/attendance')->name('admin.attendance.')->group(function () {
    Route::get('/dashboard', AttendanceDashboardController::class)
        ->middleware('permission:attendance.dashboard.view,admin')
        ->name('dashboard');

    Route::get('/records', AttendanceRecordsController::class)
        ->middleware('permission:attendance.record.view,admin')
        ->name('records');
});
