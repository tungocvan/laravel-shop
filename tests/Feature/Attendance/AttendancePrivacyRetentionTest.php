<?php

namespace Tests\Feature\Attendance;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Enums\VerificationResult;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceShift;
use Modules\Attendance\Services\AttendancePrivacyRetentionService;
use Tests\TestCase;

class AttendancePrivacyRetentionTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_raw_gps_is_removed_while_business_evidence_is_preserved(): void
    {
        config()->set('attendance.attendance.privacy.raw_gps_retention_days', 30);

        $location = AttendanceLocation::query()->create([
            'name' => 'Văn phòng chính',
            'code' => 'HQ-PRIVACY',
            'latitude' => 10.7769,
            'longitude' => 106.7009,
            'radius_meters' => 150,
            'maximum_accuracy_meters' => 100,
            'is_active' => true,
            'check_in_enabled' => true,
            'check_out_enabled' => true,
        ]);
        $shift = AttendanceShift::query()->create([
            'name' => 'Ca hành chính',
            'code' => 'PRIVACY-DAY',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_grace_minutes' => 5,
            'early_leave_grace_minutes' => 5,
            'is_active' => true,
            'is_default' => true,
        ]);

        $record = AttendanceRecord::query()->create([
            'employee_profile_id' => null,
            'user_id' => null,
            'work_date' => '2026-07-01',
            'shift_id' => $shift->id,
            'session_key' => hash('sha256', 'privacy-retention-expired'),
            'status' => AttendanceRecordStatus::Completed,
            'shift_code_snapshot' => 'PRIVACY-DAY',
            'shift_name_snapshot' => 'Ca hành chính',
            'shift_start_time_snapshot' => '08:00',
            'shift_end_time_snapshot' => '17:00',
            'late_grace_minutes_snapshot' => 5,
            'early_leave_grace_minutes_snapshot' => 5,
            'checked_in_at' => '2026-07-01 08:07:00',
            'check_in_location_id' => $location->id,
            'check_in_latitude' => 10.77691,
            'check_in_longitude' => 106.70091,
            'check_in_accuracy_meters' => 18,
            'check_in_distance_meters' => 22,
            'check_in_captured_at' => '2026-07-01 08:07:00',
            'check_in_verification_result' => VerificationResult::Verified,
            'checked_out_at' => '2026-07-01 16:55:00',
            'check_out_location_id' => $location->id,
            'check_out_latitude' => 10.77692,
            'check_out_longitude' => 106.70092,
            'check_out_accuracy_meters' => 20,
            'check_out_distance_meters' => 25,
            'check_out_captured_at' => '2026-07-01 16:55:00',
            'check_out_verification_result' => VerificationResult::Verified,
            'worked_minutes' => 528,
            'late_minutes' => 7,
            'early_leave_minutes' => 5,
        ]);

        $updated = (new AttendancePrivacyRetentionService)->purgeExpiredRawGps(
            CarbonImmutable::parse('2026-09-01 12:00:00'),
        );
        $record->refresh();

        $this->assertSame(1, $updated);
        $this->assertNull($record->check_in_latitude);
        $this->assertNull($record->check_in_longitude);
        $this->assertNull($record->check_in_accuracy_meters);
        $this->assertNull($record->check_in_captured_at);
        $this->assertNull($record->check_out_latitude);
        $this->assertNull($record->check_out_longitude);
        $this->assertNull($record->check_out_accuracy_meters);
        $this->assertNull($record->check_out_captured_at);

        $this->assertSame($location->id, $record->check_in_location_id);
        $this->assertSame($location->id, $record->check_out_location_id);
        $this->assertSame(22.0, (float) $record->check_in_distance_meters);
        $this->assertSame(25.0, (float) $record->check_out_distance_meters);
        $this->assertSame(VerificationResult::Verified, $record->check_in_verification_result);
        $this->assertSame(VerificationResult::Verified, $record->check_out_verification_result);
        $this->assertSame('2026-07-01 08:07:00', $record->checked_in_at?->format('Y-m-d H:i:s'));
        $this->assertSame('2026-07-01 16:55:00', $record->checked_out_at?->format('Y-m-d H:i:s'));
        $this->assertSame(528, $record->worked_minutes);
        $this->assertSame(7, $record->late_minutes);
        $this->assertSame(5, $record->early_leave_minutes);
    }

    public function test_recent_raw_gps_is_preserved_and_cleanup_is_idempotent(): void
    {
        config()->set('attendance.attendance.privacy.raw_gps_retention_days', 30);

        $record = AttendanceRecord::query()->create([
            'work_date' => '2026-08-20',
            'session_key' => hash('sha256', 'privacy-retention-recent'),
            'status' => AttendanceRecordStatus::CheckedIn,
            'shift_code_snapshot' => 'DEFAULT',
            'shift_name_snapshot' => 'Ca hành chính',
            'shift_start_time_snapshot' => '08:00',
            'shift_end_time_snapshot' => '17:00',
            'late_grace_minutes_snapshot' => 5,
            'early_leave_grace_minutes_snapshot' => 5,
            'checked_in_at' => '2026-08-20 08:00:00',
            'check_in_latitude' => 10.77691,
            'check_in_longitude' => 106.70091,
            'check_in_accuracy_meters' => 15,
            'check_in_distance_meters' => 10,
            'check_in_captured_at' => '2026-08-20 08:00:00',
            'check_in_verification_result' => VerificationResult::Verified,
        ]);

        $service = new AttendancePrivacyRetentionService;
        $now = CarbonImmutable::parse('2026-09-01 12:00:00');

        $this->assertSame(0, $service->purgeExpiredRawGps($now));
        $record->refresh();
        $this->assertSame(10.77691, (float) $record->check_in_latitude);

        $record->forceFill(['check_in_captured_at' => '2026-07-01 08:00:00'])->saveQuietly();

        $this->assertSame(1, $service->purgeExpiredRawGps($now));
        $this->assertSame(0, $service->purgeExpiredRawGps($now));
    }
}
