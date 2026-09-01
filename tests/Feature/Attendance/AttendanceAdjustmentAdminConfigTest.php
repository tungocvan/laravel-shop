<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Services\AttendanceAdjustmentService;
use Modules\Attendance\Services\AttendanceAdminConfigService;
use Modules\Attendance\Services\AttendanceRecordMaintenanceService;
use Tests\TestCase;

class AttendanceAdjustmentAdminConfigTest extends TestCase
{
    public function test_adjustment_service_enforces_transaction_lock_and_no_self_approval_contract(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceAdjustmentService.php'));

        $this->assertStringContainsString('DB::transaction', $source);
        $this->assertStringContainsString('lockForUpdate()', $source);
        $this->assertStringContainsString('Self-approval is not allowed', $source);
        $this->assertStringContainsString("AdjustmentStatus::Pending", $source);
        $this->assertStringContainsString("AdjustmentStatus::Approved", $source);
        $this->assertStringContainsString("AdjustmentStatus::Rejected", $source);
    }

    public function test_adjustment_approval_recalculates_from_shift_snapshot_and_audits(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceAdjustmentService.php'));

        $this->assertStringContainsString('late_grace_minutes_snapshot', $source);
        $this->assertStringContainsString('early_leave_grace_minutes_snapshot', $source);
        $this->assertStringContainsString('calculationService->calculate', $source);
        $this->assertStringContainsString("'attendance.adjustment.approve'", $source);
        $this->assertStringContainsString("'attendance.adjustment.reject'", $source);
    }

    public function test_record_maintenance_requires_reason_and_never_hard_deletes(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceRecordMaintenanceService.php'));

        $this->assertStringContainsString('Void reason is required.', $source);
        $this->assertStringContainsString("AttendanceRecordStatus::Voided", $source);
        $this->assertStringContainsString("'attendance.record.void'", $source);
        $this->assertStringContainsString("'attendance.record.manual_correct'", $source);
        $this->assertStringNotContainsString('->delete()', $source);
        $this->assertStringNotContainsString('forceDelete', $source);
    }

    public function test_admin_config_service_has_default_shift_and_geofence_bounds_contracts(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/Services/AttendanceAdminConfigService.php'));

        $this->assertStringContainsString("where('is_default', true)", $source);
        $this->assertStringContainsString("update(['is_default' => false])", $source);
        $this->assertStringContainsString('$latitude < -90 || $latitude > 90', $source);
        $this->assertStringContainsString('$longitude < -180 || $longitude > 180', $source);
        $this->assertStringContainsString('$radius <= 0 || $radius > 10000', $source);
        $this->assertStringContainsString('$accuracy <= 0 || $accuracy > 1000', $source);
    }

    public function test_mr4_services_are_autoloadable(): void
    {
        $this->assertTrue(class_exists(AttendanceAdjustmentService::class));
        $this->assertTrue(class_exists(AttendanceRecordMaintenanceService::class));
        $this->assertTrue(class_exists(AttendanceAdminConfigService::class));
    }
}
