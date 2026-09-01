<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Http\Controllers\AttendanceDemoOperationsController;
use Modules\Attendance\Http\Controllers\AttendanceLocationsController;
use Modules\Attendance\Http\Controllers\AttendanceShiftsController;
use Modules\Attendance\Services\AttendanceDemoDataService;
use Modules\Attendance\Services\AttendanceGeocodingService;
use Tests\TestCase;

class AttendanceAdminOperationsContractTest extends TestCase
{
    public function test_autoloads_attendance_admin_operation_boundaries(): void
    {
        $this->assertTrue(class_exists(AttendanceLocationsController::class));
        $this->assertTrue(class_exists(AttendanceShiftsController::class));
        $this->assertTrue(class_exists(AttendanceDemoOperationsController::class));
        $this->assertTrue(class_exists(AttendanceDemoDataService::class));
        $this->assertTrue(class_exists(AttendanceGeocodingService::class));
    }

    public function test_routes_location_and_shift_management_through_approved_permissions(): void
    {
        $routes = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

        $this->assertStringContainsString('attendance.location.view', $routes);
        $this->assertStringContainsString('attendance.location.manage', $routes);
        $this->assertStringContainsString('attendance.shift.view', $routes);
        $this->assertStringContainsString('attendance.shift.manage', $routes);
        $this->assertStringContainsString("Route::get('/locations'", $routes);
        $this->assertStringContainsString("Route::post('/locations/geocode'", $routes);
        $this->assertStringContainsString("Route::get('/shifts'", $routes);
    }

    public function test_keeps_demo_reset_local_or_testing_and_attendance_scoped(): void
    {
        $service = file_get_contents(base_path('Modules/Attendance/Services/AttendanceDemoDataService.php'));
        $routes = file_get_contents(base_path('Modules/Attendance/routes/web.php'));

        $this->assertStringContainsString("environment(['local', 'testing'])", $service);
        $this->assertStringContainsString("environment(['local', 'testing'])", $routes);
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

    public function test_dashboard_links_admin_operation_workspaces_and_hides_demo_outside_local_testing(): void
    {
        $dashboard = file_get_contents(base_path('Modules/Attendance/resources/views/admin/dashboard.blade.php'));

        $this->assertStringContainsString("route('admin.attendance.locations.index')", $dashboard);
        $this->assertStringContainsString("route('admin.attendance.shifts.index')", $dashboard);
        $this->assertStringContainsString("route('admin.attendance.demo.index')", $dashboard);
        $this->assertStringContainsString("environment(['local', 'testing'])", $dashboard);
    }

    public function test_location_ui_supports_address_geocoding_and_on_demand_device_position(): void
    {
        $view = file_get_contents(base_path('Modules/Attendance/resources/views/admin/locations.blade.php'));
        $model = file_get_contents(base_path('Modules/Attendance/Models/AttendanceLocation.php'));
        $controller = file_get_contents(base_path('Modules/Attendance/Http/Controllers/AttendanceLocationsController.php'));
        $geocoder = file_get_contents(base_path('Modules/Attendance/Services/AttendanceGeocodingService.php'));
        $config = file_get_contents(base_path('Modules/Attendance/config/attendance.php'));

        $this->assertStringContainsString('name="address"', $view);
        $this->assertStringContainsString('data-geocode-address', $view);
        $this->assertStringContainsString('navigator.geolocation.getCurrentPosition', $view);
        $this->assertStringNotContainsString('watchPosition', $view);
        $this->assertStringContainsString("'address',", $model);
        $this->assertStringContainsString("'address' => ['nullable', 'string', 'max:500']", $controller);
        $this->assertStringContainsString("config('attendance.attendance.geocoding.endpoint')", $geocoder);
        $this->assertStringContainsString('withUserAgent', $geocoder);
        $this->assertStringContainsString('nominatim.openstreetmap.org/search', $config);
    }
}
