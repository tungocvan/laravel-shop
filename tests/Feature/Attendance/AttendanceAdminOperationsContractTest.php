<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Http\Controllers\AttendanceDemoOperationsController;
use Modules\Attendance\Http\Controllers\AttendanceLocationsController;
use Modules\Attendance\Http\Controllers\AttendanceShiftsController;
use Modules\Attendance\Services\AttendanceDemoDataService;
use Tests\TestCase;

class AttendanceAdminOperationsContractTest extends TestCase
{
    public function test_autoloads_attendance_admin_operation_boundaries(): void
    {
        $this->assertTrue(class_exists(AttendanceLocationsController::class));
        $this->assertTrue(class_exists(AttendanceShiftsController::class));
        $this->assertTrue(class_exists(AttendanceDemoOperationsController::class));
        $this->assertTrue(class_exists(AttendanceDemoDataService::class));
    }

    public function test_routes_location_and_shift_management_through_approved_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

        $this->assertStringContainsString('attendance.location.view', $routes);
        $this->assertStringContainsString('attendance.location.manage', $routes);
        $this->assertStringContainsString('attendance.shift.view', $routes);
        $this->assertStringContainsString('attendance.shift.manage', $routes);
        $this->assertStringContainsString("Route::get('/locations'", $routes);
        $this->assertStringContainsString("Route::get('/shifts'", $routes);
    }

    public function test_keeps_demo_reset_local_or_testing_and_attendance_scoped(): void
    {
        $service = file_get_contents(base_path('Modules/Attendance/Services/AttendanceDemoDataService.php'));

        $this->assertStringContainsString("environment(['local', 'testing'])", $service);
        $this->assertStringContainsString("where('session_key', 'like', 'demo-%')", $service);
        $this->assertStringContainsString('AttendanceAdjustmentRequest::query()', $service);
        $this->assertStringContainsString('AttendanceAuditEvent::query()', $service);
        $this->assertStringNotContainsString('User::query()->delete', $service);
        $this->assertStringNotContainsString('EmployeeProfile::query()->delete', $service);
    }

    public function test_does_not_overwrite_an_existing_demo_hq_configuration(): void
    {
        $seeder = file_get_contents(base_path('Modules/Attendance/Database/Seeders/AttendanceDemoSeeder.php'));

        $this->assertStringContainsString("firstOrCreate(\n            ['code' => 'DEMO-HQ']", $seeder);
        $this->assertStringNotContainsString("updateOrCreate(\n            ['code' => 'DEMO-HQ']", $seeder);
        $this->assertStringContainsString('[DEMO] ', $seeder);
        $this->assertStringContainsString('AdjustmentStatus::Pending', $seeder);
        $this->assertStringContainsString('AdjustmentStatus::Approved', $seeder);
        $this->assertStringContainsString('AdjustmentStatus::Rejected', $seeder);
    }

    public function test_uses_bounded_pagination_for_admin_config_lists(): void
    {
        $locations = file_get_contents(base_path('Modules/Attendance/Http/Controllers/AttendanceLocationsController.php'));
        $shifts = file_get_contents(base_path('Modules/Attendance/Http/Controllers/AttendanceShiftsController.php'));

        $this->assertStringContainsString('paginate(10)', $locations);
        $this->assertStringContainsString('paginate(10)', $shifts);
    }
}
