<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Account\Models\EmployeeProfile;
use Modules\Attendance\Enums\AdjustmentStatus;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceAdjustmentService
{
    public function __construct(
        private readonly AttendanceCalculationService $calculationService,
        private readonly AttendanceAuditService $auditService,
    ) {}

    public function submit(
        EmployeeProfile $employeeProfile,
        CarbonImmutable $workDate,
        ?CarbonImmutable $requestedCheckInAt,
        ?CarbonImmutable $requestedCheckOutAt,
        string $reason,
        ?string $note = null,
        ?AttendanceRecord $record = null,
    ): AttendanceAdjustmentRequest {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Adjustment reason is required.');
        }

        if (! $employeeProfile->exists || ! $employeeProfile->user_id) {
            throw new DomainException('Adjustment requires a persisted employee profile linked to a user.');
        }

        if ($requestedCheckInAt && $requestedCheckOutAt && $requestedCheckOutAt->lessThan($requestedCheckInAt)) {
            throw new DomainException('Requested check-out cannot be before requested check-in.');
        }

        return DB::transaction(function () use ($employeeProfile, $workDate, $requestedCheckInAt, $requestedCheckOutAt, $reason, $note, $record): AttendanceAdjustmentRequest {
            $request = AttendanceAdjustmentRequest::query()->create([
                'employee_profile_id' => $employeeProfile->getKey(),
                'user_id' => $employeeProfile->user_id,
                'attendance_record_id' => $record?->getKey(),
                'requested_work_date' => $workDate->toDateString(),
                'requested_check_in_at' => $requestedCheckInAt,
                'requested_check_out_at' => $requestedCheckOutAt,
                'reason' => $reason,
                'note' => $note,
                'status' => AdjustmentStatus::Pending,
                'submitted_at' => CarbonImmutable::now(),
            ]);

            if ($record) {
                $this->auditService->record(
                    'attendance.adjustment.submit',
                    (int) $employeeProfile->user_id,
                    $record,
                    metadata: ['adjustment_request_id' => $request->getKey()],
                    reason: $reason,
                );
            }

            return $request;
        });
    }

    public function approve(AttendanceAdjustmentRequest $request, int $reviewerUserId, ?string $reviewNote = null): AttendanceAdjustmentRequest
    {
        return DB::transaction(function () use ($request, $reviewerUserId, $reviewNote): AttendanceAdjustmentRequest {
            $locked = AttendanceAdjustmentRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $this->assertPending($locked);
            $this->assertNotSelfApproval($locked, $reviewerUserId);

            $record = $locked->attendance_record_id
                ? AttendanceRecord::query()->whereKey($locked->attendance_record_id)->lockForUpdate()->firstOrFail()
                : null;

            if (! $record) {
                throw new DomainException('Adjustment approval requires an existing attendance record.');
            }

            $before = $this->recordSummary($record);
            $checkIn = $locked->requested_check_in_at ? CarbonImmutable::instance($locked->requested_check_in_at) : CarbonImmutable::instance($record->checked_in_at);
            $checkOut = $locked->requested_check_out_at ? CarbonImmutable::instance($locked->requested_check_out_at) : ($record->checked_out_at ? CarbonImmutable::instance($record->checked_out_at) : null);

            if (! $checkOut) {
                throw new DomainException('Approved adjustment requires a check-out time.');
            }

            [$shiftStart, $shiftEnd] = $this->snapshotWindow($record, $checkIn);
            $metrics = $this->calculationService->calculate(
                $checkIn,
                $checkOut,
                $shiftStart,
                $shiftEnd,
                (int) $record->late_grace_minutes_snapshot,
                (int) $record->early_leave_grace_minutes_snapshot,
            );

            $record->fill([
                'checked_in_at' => $checkIn,
                'checked_out_at' => $checkOut,
                ...$metrics,
                'adjusted_at' => CarbonImmutable::now(),
            ])->save();

            $locked->fill([
                'status' => AdjustmentStatus::Approved,
                'reviewed_at' => CarbonImmutable::now(),
                'reviewed_by' => $reviewerUserId,
                'review_note' => $reviewNote,
            ])->save();

            $this->auditService->record(
                'attendance.adjustment.approve',
                $reviewerUserId,
                $record,
                before: $before,
                after: $this->recordSummary($record),
                metadata: ['adjustment_request_id' => $locked->getKey()],
                reason: $locked->reason,
            );

            return $locked->refresh();
        });
    }

    public function reject(AttendanceAdjustmentRequest $request, int $reviewerUserId, string $reviewNote): AttendanceAdjustmentRequest
    {
        $reviewNote = trim($reviewNote);
        if ($reviewNote === '') {
            throw new DomainException('Review note is required when rejecting an adjustment.');
        }

        return DB::transaction(function () use ($request, $reviewerUserId, $reviewNote): AttendanceAdjustmentRequest {
            $locked = AttendanceAdjustmentRequest::query()->whereKey($request->getKey())->lockForUpdate()->firstOrFail();
            $this->assertPending($locked);
            $this->assertNotSelfApproval($locked, $reviewerUserId);

            $locked->fill([
                'status' => AdjustmentStatus::Rejected,
                'reviewed_at' => CarbonImmutable::now(),
                'reviewed_by' => $reviewerUserId,
                'review_note' => $reviewNote,
            ])->save();

            if ($locked->attendance_record_id) {
                $record = AttendanceRecord::query()->find($locked->attendance_record_id);
                if ($record) {
                    $this->auditService->record(
                        'attendance.adjustment.reject',
                        $reviewerUserId,
                        $record,
                        metadata: ['adjustment_request_id' => $locked->getKey()],
                        reason: $reviewNote,
                    );
                }
            }

            return $locked->refresh();
        });
    }

    private function assertPending(AttendanceAdjustmentRequest $request): void
    {
        if ($request->status !== AdjustmentStatus::Pending) {
            throw new DomainException('Only pending adjustments can be reviewed.');
        }
    }

    private function assertNotSelfApproval(AttendanceAdjustmentRequest $request, int $reviewerUserId): void
    {
        if ((int) $request->user_id === $reviewerUserId) {
            throw new DomainException('Self-approval is not allowed for attendance adjustments.');
        }
    }

    private function snapshotWindow(AttendanceRecord $record, CarbonImmutable $reference): array
    {
        $start = CarbonImmutable::parse($record->work_date->format('Y-m-d').' '.$record->shift_start_time_snapshot, $reference->getTimezone());
        $end = CarbonImmutable::parse($record->work_date->format('Y-m-d').' '.$record->shift_end_time_snapshot, $reference->getTimezone());

        if ($end->lessThanOrEqualTo($start)) {
            $end = $end->addDay();
        }

        return [$start, $end];
    }

    private function recordSummary(AttendanceRecord $record): array
    {
        return [
            'checked_in_at' => $record->checked_in_at?->toIso8601String(),
            'checked_out_at' => $record->checked_out_at?->toIso8601String(),
            'worked_minutes' => $record->worked_minutes,
            'late_minutes' => $record->late_minutes,
            'early_leave_minutes' => $record->early_leave_minutes,
            'adjusted_at' => $record->adjusted_at?->toIso8601String(),
        ];
    }
}
