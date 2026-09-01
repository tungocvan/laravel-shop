<?php

namespace Tests\Feature\ClientPortal;

use Modules\ClientPortal\Applications\Attendance\Http\Controllers\AttendanceApplicationController;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class AttendanceApplicationContractTest extends TestCase
{
    #[Test]
    public function attendance_application_manifest_declares_canonical_module_and_permissions(): void
    {
        $manifest = require base_path('Modules/ClientPortal/Applications/Attendance/manifest.php');

        $this->assertSame('attendance', $manifest['key']);
        $this->assertSame('Attendance', $manifest['source_module']);
        $this->assertSame('client.attendance.access', $manifest['permission']);
        $this->assertSame('attendance.record.view-own', $manifest['features']['today']['permission']);
        $this->assertSame('attendance.check-in', $manifest['features']['today']['actions']['check-in']['permission']);
        $this->assertSame('attendance.check-out', $manifest['features']['today']['actions']['check-out']['permission']);
        $this->assertSame('attendance.adjustment.create', $manifest['features']['adjustments']['permission']);
    }

    #[Test]
    public function attendance_routes_are_module_gated_and_use_client_application_boundary(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Attendance/routes.php'));

        $this->assertStringContainsString("config('modules.registry.Attendance.enabled', false)", $routes);
        $this->assertStringContainsString("'auth:web'", $routes);
        $this->assertStringContainsString("'client.application:attendance'", $routes);
        $this->assertStringContainsString('permission:attendance.check-in,web', $routes);
        $this->assertStringContainsString('permission:attendance.check-out,web', $routes);
        $this->assertStringContainsString('permission:attendance.adjustment.create,web', $routes);
    }

    #[Test]
    public function attendance_controller_delegates_mutations_to_domain_services(): void
    {
        $this->assertTrue(class_exists(AttendanceApplicationController::class));

        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Attendance/Http/Controllers/AttendanceApplicationController.php'));

        $this->assertStringContainsString('AttendanceService $attendanceService', $controller);
        $this->assertStringContainsString('AttendanceAdjustmentService $adjustmentService', $controller);
        $this->assertStringContainsString('$attendanceService->{$method}', $controller);
        $this->assertStringContainsString('$adjustmentService->submit(', $controller);
    }

    #[Test]
    public function history_and_adjustment_queries_are_scoped_to_the_authenticated_user(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Attendance/Http/Controllers/AttendanceApplicationController.php'));

        $this->assertGreaterThanOrEqual(3, substr_count($controller, "->where('user_id', $userId)"));
        $this->assertStringContainsString("->where('user_id', $user->getAuthIdentifier())", $controller);
        $this->assertStringContainsString('->paginate(10)', $controller);
    }

    #[Test]
    public function attendance_pwa_uses_on_demand_geolocation_and_blocks_offline_submission(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/attendance/dashboard.blade.php'));

        $this->assertStringContainsString('navigator.geolocation.getCurrentPosition', $view);
        $this->assertStringContainsString('if (!navigator.onLine)', $view);
        $this->assertStringContainsString('maximumAge: 0', $view);
        $this->assertStringContainsString('enableHighAccuracy: true', $view);
        $this->assertStringContainsString('Không thể chấm công khi ngoại tuyến', $view);
        $this->assertStringNotContainsString('watchPosition', $view);
    }

    #[Test]
    public function client_views_do_not_expose_precise_attendance_coordinates(): void
    {
        $history = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/attendance/history.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/attendance/dashboard.blade.php'));

        foreach (['check_in_latitude', 'check_in_longitude', 'check_out_latitude', 'check_out_longitude'] as $field) {
            $this->assertStringNotContainsString($field, $history);
            $this->assertStringNotContainsString($field, $dashboard);
        }
    }
}
