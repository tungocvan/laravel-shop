<?php

namespace Tests\Feature\Attendance;

use Modules\Attendance\Enums\AdjustmentStatus;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Enums\VerificationResult;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceAuditEvent;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceShift;
use Tests\TestCase;

class AttendancePersistenceContractTest extends TestCase
{
    public function test_manifest_declares_all_attendance_owned_tables(): void
    {
        $manifest = require base_path('Modules/Attendance/config/module.php');

        $this->assertSame([
            'attendance_locations',
            'attendance_shifts',
            'attendance_records',
            'attendance_adjustment_requests',
            'attendance_audit_events',
        ], $manifest['tables']);
    }

    public function test_persistent_enums_match_the_approved_release_one_states(): void
    {
        $this->assertSame(['checked_in', 'completed', 'voided'], array_column(AttendanceRecordStatus::cases(), 'value'));
        $this->assertSame(['pending', 'approved', 'rejected'], array_column(AdjustmentStatus::cases(), 'value'));
        $this->assertSame(
            ['verified', 'accuracy_low', 'outside_area', 'location_unavailable'],
            array_column(VerificationResult::cases(), 'value'),
        );
    }

    public function test_models_are_bound_to_attendance_owned_tables(): void
    {
        $this->assertSame('attendance_locations', (new AttendanceLocation)->getTable());
        $this->assertSame('attendance_shifts', (new AttendanceShift)->getTable());
        $this->assertSame('attendance_records', (new AttendanceRecord)->getTable());
        $this->assertSame('attendance_adjustment_requests', (new AttendanceAdjustmentRequest)->getTable());
        $this->assertSame('attendance_audit_events', (new AttendanceAuditEvent)->getTable());
    }

    public function test_record_migration_preserves_session_identity_and_history_contract(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/database/migrations/2026_09_01_100003_create_attendance_records_table.php'));

        $this->assertStringContainsString("foreignId('employee_profile_id')->constrained('employee_profiles')->restrictOnDelete()", $source);
        $this->assertStringContainsString("string('session_key')->unique()", $source);
        $this->assertStringNotContainsString("unique(['user_id', 'work_date'])", $source);
        $this->assertStringContainsString('shift_code_snapshot', $source);
        $this->assertStringContainsString('shift_start_time_snapshot', $source);
        $this->assertStringContainsString('check_in_latitude', $source);
        $this->assertStringContainsString('check_out_latitude', $source);
        $this->assertStringContainsString('voided_at', $source);
    }

    public function test_default_seeder_contains_no_fake_location_seed(): void
    {
        $source = file_get_contents(base_path('Modules/Attendance/database/seeders/AttendanceDefaultsSeeder.php'));

        $this->assertStringContainsString("['code' => 'DEFAULT']", $source);
        $this->assertStringNotContainsString('AttendanceLocation', $source);
        $this->assertStringNotContainsString('latitude', $source);
        $this->assertStringNotContainsString('longitude', $source);
    }
}
