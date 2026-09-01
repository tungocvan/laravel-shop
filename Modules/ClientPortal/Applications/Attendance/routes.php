<?php

use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Applications\Attendance\Http\Controllers\AttendanceApplicationController;

if ((bool) config('modules.registry.Attendance.enabled', false)) {
    Route::middleware([
        'web',
        'auth:web',
        'client.application:attendance',
    ])->prefix('apps/attendance')->name('client.attendance.')->group(function (): void {
        Route::get('/', [AttendanceApplicationController::class, 'dashboard'])
            ->middleware('client.feature:attendance,today')
            ->name('dashboard');

        Route::post('/check-in', [AttendanceApplicationController::class, 'checkIn'])
            ->middleware('permission:attendance.check-in,web')
            ->name('check-in');

        Route::post('/check-out', [AttendanceApplicationController::class, 'checkOut'])
            ->middleware('permission:attendance.check-out,web')
            ->name('check-out');

        Route::get('/history', [AttendanceApplicationController::class, 'history'])
            ->middleware('client.feature:attendance,history')
            ->name('history');

        Route::get('/adjustments', [AttendanceApplicationController::class, 'adjustments'])
            ->middleware('client.feature:attendance,adjustments')
            ->name('adjustments');

        Route::post('/adjustments', [AttendanceApplicationController::class, 'submitAdjustment'])
            ->middleware('permission:attendance.adjustment.create,web')
            ->name('adjustments.store');
    });
}
