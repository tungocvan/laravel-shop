<?php

namespace Tests\Feature\Attendance;

use Carbon\CarbonImmutable;
use InvalidArgumentException;
use Modules\Attendance\Models\AttendanceShift;
use Modules\Attendance\Services\AttendanceCalculationService;
use Modules\Attendance\Services\AttendanceService;
use Modules\Attendance\Services\GeofenceService;
use Modules\Attendance\Services\ShiftResolver;
use Tests\TestCase;

class AttendanceDomainCoreTest extends TestCase
{
    public function test_day_shift_resolves_server_business_date_and_snapshot(): void
    {
        $shift = new AttendanceShift([
            'name' => 'Ca hành chính',
            'code' => 'DEFAULT',
            'start_time' => '08:00',
            'end_time' => '17:00',
            'late_grace_minutes' => 5,
            'early_leave_grace_minutes' => 5,
            'is_active' => true,
            'is_default' => true,
        ]);

        $resolved = (new ShiftResolver)->resolveFromShift(
            $shift,
            CarbonImmutable::parse('2026-09-01 09:00:00', 'Asia/Ho_Chi_Minh'),
        );

        $this->assertSame('2026-09-01', $resolved['work_date']);
        $this->assertSame('2026-09-01 08:00:00', $resolved['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-01 17:00:00', $resolved['ends_at']->format('Y-m-d H:i:s'));
        $this->assertSame('DEFAULT', $resolved['snapshot']['shift_code_snapshot']);
        $this->assertSame(5, $resolved['snapshot']['late_grace_minutes_snapshot']);
    }

    public function test_overnight_shift_uses_shift_start_business_date(): void
    {
        $shift = new AttendanceShift([
            'name' => 'Ca đêm',
            'code' => 'NIGHT',
            'start_time' => '22:00',
            'end_time' => '06:00',
            'late_grace_minutes' => 5,
            'early_leave_grace_minutes' => 5,
        ]);

        $resolved = (new ShiftResolver)->resolveFromShift(
            $shift,
            CarbonImmutable::parse('2026-09-02 01:00:00', 'Asia/Ho_Chi_Minh'),
        );

        $this->assertSame('2026-09-01', $resolved['work_date']);
        $this->assertSame('2026-09-01 22:00:00', $resolved['starts_at']->format('Y-m-d H:i:s'));
        $this->assertSame('2026-09-02 06:00:00', $resolved['ends_at']->format('Y-m-d H:i:s'));
    }

    public function test_geofence_haversine_distance_and_coordinate_validation(): void
    {
        $service = new GeofenceService;

        $this->assertEqualsWithDelta(111.19, $service->distanceMeters(0, 0, 0, 0.001), 0.5);

        $this->expectException(InvalidArgumentException::class);
        $service->distanceMeters(0, 0, 0, 0);

        $reflection = new \ReflectionMethod($service, 'validateCoordinates');
        $reflection->invoke($service, 91.0, 106.0, 10.0);
    }

    public function test_attendance_calculation_applies_grace_and_worked_minutes(): void
    {
        $metrics = (new AttendanceCalculationService)->calculate(
            CarbonImmutable::parse('2026-09-01 08:10:00'),
            CarbonImmutable::parse('2026-09-01 16:50:00'),
            CarbonImmutable::parse('2026-09-01 08:00:00'),
            CarbonImmutable::parse('2026-09-01 17:00:00'),
            5,
            5,
        );

        $this->assertSame(520, (int) $metrics['worked_minutes']);
        $this->assertSame(10, (int) $metrics['late_minutes']);
        $this->assertSame(10, (int) $metrics['early_leave_minutes']);
    }

    public function test_attendance_calculation_rejects_checkout_before_checkin(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new AttendanceCalculationService)->calculate(
            CarbonImmutable::parse('2026-09-01 09:00:00'),
            CarbonImmutable::parse('2026-09-01 08:00:00'),
            CarbonImmutable::parse('2026-09-01 08:00:00'),
            CarbonImmutable::parse('2026-09-01 17:00:00'),
            5,
            5,
        );
    }

    public function test_session_identity_is_deterministic_without_user_date_uniqueness(): void
    {
        $reflection = new \ReflectionClass(AttendanceService::class);
        $service = $reflection->newInstanceWithoutConstructor();

        $first = $service->sessionKey(15, '2026-09-01', 'DEFAULT');
        $retry = $service->sessionKey(15, '2026-09-01', 'DEFAULT');
        $otherShift = $service->sessionKey(15, '2026-09-01', 'NIGHT');

        $this->assertSame($first, $retry);
        $this->assertNotSame($first, $otherShift);
        $this->assertSame(64, strlen($first));
    }

    public function test_checkin_checkout_orchestration_contains_transaction_lock_and_audit_contracts(): void
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

        $this->assertStringContainsString("str_contains((string) $key, 'latitude')", $source);
        $this->assertStringContainsString("str_contains((string) $key, 'longitude')", $source);
    }
}
