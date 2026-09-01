<?php

use Modules\Attendance\Http\Controllers\AttendanceDemoOperationsController;
use Modules\Attendance\Http\Controllers\AttendanceLocationsController;
use Modules\Attendance\Http\Controllers\AttendanceShiftsController;
use Modules\Attendance\Services\AttendanceDemoDataService;

it('autoloads attendance admin operation boundaries', function () {
    expect(class_exists(AttendanceLocationsController::class))->toBeTrue()
        ->and(class_exists(AttendanceShiftsController::class))->toBeTrue()
        ->and(class_exists(AttendanceDemoOperationsController::class))->toBeTrue()
        ->and(class_exists(AttendanceDemoDataService::class))->toBeTrue();
});

it('routes location and shift management through approved permissions', function () {
    $routes = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

    expect($routes)
        ->toContain('attendance.location.view')
        ->toContain('attendance.location.manage')
        ->toContain('attendance.shift.view')
        ->toContain('attendance.shift.manage')
        ->toContain("Route::get('/locations'")
        ->toContain("Route::get('/shifts'");
});

it('keeps demo reset local or testing and attendance scoped', function () {
    $service = file_get_contents(base_path('Modules/Attendance/Services/AttendanceDemoDataService.php'));

    expect($service)
        ->toContain("environment(['local', 'testing'])")
        ->toContain("where('session_key', 'like', 'demo-%')")
        ->toContain('AttendanceAdjustmentRequest::query()')
        ->toContain('AttendanceAuditEvent::query()')
        ->not->toContain('User::query()->delete')
        ->not->toContain('EmployeeProfile::query()->delete');
});

it('does not overwrite an existing demo hq configuration', function () {
    $seeder = file_get_contents(base_path('Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php'));

    expect($seeder)
        ->toContain("firstOrCreate(\n            ['code' => 'DEMO-HQ']")
        ->not->toContain("updateOrCreate(\n            ['code' => 'DEMO-HQ']")
        ->toContain('[DEMO] ')
        ->toContain('AdjustmentStatus::Pending')
        ->toContain('AdjustmentStatus::Approved')
        ->toContain('AdjustmentStatus::Rejected');
});

it('uses bounded pagination for admin config lists', function () {
    $locations = file_get_contents(base_path('Modules/Attendance/Http/Controllers/AttendanceLocationsController.php'));
    $shifts = file_get_contents(base_path('Modules/Attendance/Http/Controllers/AttendanceShiftsController.php'));

    expect($locations)->toContain('paginate(10)')
        ->and($shifts)->toContain('paginate(10)');
});
