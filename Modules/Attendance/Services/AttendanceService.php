<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Modules\Account\Models\EmployeeProfile;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Enums\VerificationResult;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceService
{
    public function __construct(
        private readonly ShiftResolver $shiftResolver,
        private readonly GeofenceService $geofenceService,
        private readonly AttendanceCalculationService $calculationService,
        private readonly AttendanceAuditService $auditService,
    ) {}

    public function checkIn(
        EmployeeProfile $employeeProfile,
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        CarbonImmutable $capturedAt,
        ?CarbonImmutable $now = null,
    ): AttendanceRecord {
        $this->assertEmployeeMayAttend($employeeProfile);
        $now ??= CarbonImmutable::now();
        $shift = $this->shiftResolver->resolve($now);
        $evidence = $this->geofenceService->verify(
            $latitude,
            $longitude,
            $accuracyMeters,
            $capturedAt,
            'check_in',
        );
        $this->assertVerified($evidence['verification_result']);

        $sessionKey = $this->sessionKey(
            (int) $employeeProfile->getKey(),
            $shift['work_date'],
            (string) $shift['shift']->code,
        );

        return DB::transaction(function () use ($employeeProfile, $now, $shift, $evidence, $sessionKey): AttendanceRecord {
            $existing = AttendanceRecord::query()
                ->where('session_key', $sessionKey)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                if ($existing->status === AttendanceRecordStatus::Voided) {
                    throw new DomainException('This attendance session has been voided.');
                }

                return $existing;
            }

            $attributes = array_merge($shift['snapshot'], [
                'employee_profile_id' => $employeeProfile->getKey(),
                'user_id' => $employeeProfile->user_id,
                'work_date' => $shift['work_date'],
                'session_key' => $sessionKey,
                'status' => AttendanceRecordStatus::CheckedIn,
                'checked_in_at' => $now,
                'check_in_location_id' => $evidence['location_id'],
                'check_in_latitude' => $evidence['latitude'],
                'check_in_longitude' => $evidence['longitude'],
                'check_in_accuracy_meters' => $evidence['accuracy_meters'],
                'check_in_distance_meters' => $evidence['distance_meters'],
                'check_in_captured_at' => $evidence['captured_at'],
                'check_in_verification_result' => $evidence['verification_result'],
                'late_minutes' => $now->gt($shift['starts_at']->addMinutes($shift['snapshot']['late_grace_minutes_snapshot']))
                    ? $shift['starts_at']->diffInMinutes($now)
                    : 0,
                'early_leave_minutes' => 0,
            ]);

            try {
                $record = AttendanceRecord::query()->create($attributes);
            } catch (QueryException $exception) {
                $record = AttendanceRecord::query()->where('session_key', $sessionKey)->first();

                if (! $record) {
                    throw $exception;
                }
            }

            $this->auditService->record(
                'attendance.check_in',
                (int) $employeeProfile->user_id,
                $record,
                after: [
                    'status' => $record->status->value,
                    'checked_in_at' => $record->checked_in_at?->toIso8601String(),
                    'location_id' => $record->check_in_location_id,
                    'verification_result' => $record->check_in_verification_result?->value,
                ],
            );

            return $record;
        });
    }

    public function checkOut(
        EmployeeProfile $employeeProfile,
        float $latitude,
        float $longitude,
        float $accuracyMeters,
        CarbonImmutable $capturedAt,
        ?CarbonImmutable $now = null,
    ): AttendanceRecord {
        $this->assertEmployeeMayAttend($employeeProfile);
        $now ??= CarbonImmutable::now();
        $shift = $this->shiftResolver->resolve($now);
        $evidence = $this->geofenceService->verify(
            $latitude,
            $longitude,
            $accuracyMeters,
            $capturedAt,
            'check_out',
        );
        $this->assertVerified($evidence['verification_result']);

        $sessionKey = $this->sessionKey(
            (int) $employeeProfile->getKey(),
            $shift['work_date'],
            (string) $shift['shift']->code,
        );

        return DB::transaction(function () use ($employeeProfile, $now, $evidence, $sessionKey): AttendanceRecord {
            $record = AttendanceRecord::query()
                ->where('session_key', $sessionKey)
                ->lockForUpdate()
                ->first();

            if (! $record) {
                throw new DomainException('No active attendance session was found for check-out.');
            }

            if ($record->status === AttendanceRecordStatus::Completed) {
                return $record;
            }

            if ($record->status !== AttendanceRecordStatus::CheckedIn || ! $record->checked_in_at) {
                throw new DomainException('Attendance session is not eligible for check-out.');
            }

            [$shiftStartsAt, $shiftEndsAt] = $this->snapshotWindow($record, $now);
            $metrics = $this->calculationService->calculate(
                CarbonImmutable::instance($record->checked_in_at),
                $now,
                $shiftStartsAt,
                $shiftEndsAt,
                (int) $record->late_grace_minutes_snapshot,
                (int) $record->early_leave_grace_minutes_snapshot,
            );

            $before = [
                'status' => $record->status->value,
                'checked_out_at' => null,
            ];

            $record->fill([
                'status' => AttendanceRecordStatus::Completed,
                'checked_out_at' => $now,
                'check_out_location_id' => $evidence['location_id'],
                'check_out_latitude' => $evidence['latitude'],
                'check_out_longitude' => $evidence['longitude'],
                'check_out_accuracy_meters' => $evidence['accuracy_meters'],
                'check_out_distance_meters' => $evidence['distance_meters'],
                'check_out_captured_at' => $evidence['captured_at'],
                'check_out_verification_result' => $evidence['verification_result'],
                ...$metrics,
            ])->save();

            $this->auditService->record(
                'attendance.check_out',
                (int) $employeeProfile->user_id,
                $record,
                before: $before,
                after: [
                    'status' => $record->status->value,
                    'checked_out_at' => $record->checked_out_at?->toIso8601String(),
                    'location_id' => $record->check_out_location_id,
                    'verification_result' => $record->check_out_verification_result?->value,
                    'worked_minutes' => $record->worked_minutes,
                    'late_minutes' => $record->late_minutes,
                    'early_leave_minutes' => $record->early_leave_minutes,
                ],
            );

            return $record->refresh();
        });
    }

    public function sessionKey(int $employeeProfileId, string $workDate, string $shiftCode): string
    {
        return hash('sha256', implode('|', [$employeeProfileId, $workDate, $shiftCode]));
    }

    private function snapshotWindow(AttendanceRecord $record, CarbonImmutable $reference): array
    {
        $start = CarbonImmutable::parse(
            $record->work_date->format('Y-m-d').' '.$record->shift_start_time_snapshot,
            $reference->getTimezone(),
        );
        $end = CarbonImmutable::parse(
            $record->work_date->format('Y-m-d').' '.$record->shift_end_time_snapshot,
            $reference->getTimezone(),
        );

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    private function assertEmployeeMayAttend(EmployeeProfile $employeeProfile): void
    {
        if (! $employeeProfile->exists || ! $employeeProfile->user_id) {
            throw new DomainException('Attendance requires a persisted employee profile linked to a user.');
        }

        if ($employeeProfile->status !== 'active') {
            throw new DomainException('Attendance is only available to active employees.');
        }
    }

    private function assertVerified(VerificationResult $result): void
    {
        if ($result !== VerificationResult::Verified) {
            throw new DomainException('Attendance location verification failed: '.$result->value.'.');
        }
    }
}
