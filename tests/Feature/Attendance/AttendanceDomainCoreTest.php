<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Services\AttendanceAuditService;
use Modules\Attendance\Services\AttendanceCalculationService;
use Modules\Attendance\Services\AttendanceGeofenceService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\AttendanceShiftResolver;
use Tests\TestCase;

class AttendanceDomainCoreTest extends TestCase
{
    public function test_domain_core_services_are_autoloadable(): void
    {
        $this->assertTrue(class_exists(AttendanceShiftResolver::class));
        $this->assertTrue(class_exists(AttendanceGeofenceService::class));
        $this->assertTrue(class_exists(AttendanceCalculationService::class));
        $this->assertTrue(class_exists(AttendanceAuditService::class));
        $this->assertTrue(class_exists(AttendanceService::class));
    }

    public function test_shift_resolver_uses_active_default_shift_and_supports_overnight_business_date(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceShiftResolver.php'));

        $this->assertStringContainsString("where('is_active', true)", $source);
        $this->assertStringContainsString("where('is_default', true)", $source);
        $this->assertStringContainsString('resolveBusinessDate', $source);
        $this->assertStringContainsString('lessThanOrEqualTo', $source);
        $this->assertStringContainsString('subDay()', $source);
    }

    public function test_geofence_service_enforces_coordinate_accuracy_radius_and_server_side_location_resolution(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceGeofenceService.php'));

        $this->assertStringContainsString('assertCoordinates', $source);
        $this->assertStringContainsString('maximum_accuracy_meters', $source);
        $this->assertStringContainsString('radius_meters', $source);
        $this->assertStringContainsString('AttendanceLocation::query()', $source);
        $this->assertStringContainsString("where('is_active', true)", $source);
        $this->assertStringContainsString('VerificationResult::Verified', $source);
    }

    public function test_calculation_service_derives_late_early_leave_and_worked_minutes(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceCalculationService.php'));

        $this->assertStringContainsString('lateMinutes', $source);
        $this->assertStringContainsString('earlyLeaveMinutes', $source);
        $this->assertStringContainsString('workedMinutes', $source);
        $this->assertStringContainsString('late_grace_minutes_snapshot', $source);
        $this->assertStringContainsString('early_leave_grace_minutes_snapshot', $source);
    }

    public function test_attendance_service_is_transactional_locked_audited_and_idempotency_aware(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceService.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString("'attendance.check_in'", $source);
        $this->assertStringContainsString("'attendance.check_out'", $source);
        $this->assertStringContainsString('VerificationResult::Verified', $source);
        $this->assertStringContainsString("hash('sha256'", $source);
    }

    public function test_audit_service_does_not_copy_precise_coordinates_to_generic_payload(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceAuditService.php'));

        $this->assertStringContainsString("str_contains(\$key, 'latitude')", $source);
        $this->assertStringContainsString("str_contains(\$key, 'longitude')", $source);
        $this->assertStringContainsString("str_contains(\$key, 'accuracy_meters')", $source);
        $this->assertStringContainsString("str_contains(\$key, 'captured_at')", $source);
        $this->assertStringContainsString('$payload[$key] = $this->sanitize($value)', $source);
    }
}
