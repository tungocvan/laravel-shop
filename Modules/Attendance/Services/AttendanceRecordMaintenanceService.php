<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Illuminate\Support\Facades\DB;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceRecordMaintenanceService
{
    public function __construct(
        private readonly AttendanceCalculationService $calculationService,
        private readonly AttendanceAuditService $auditService,
    ) {}

    public function void(AttendanceRecord $record, int $actorUserId, string $reason): AttendanceRecord
    {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Void reason is required.');
        }

        return DB::transaction(function () use ($record, $actorUserId, $reason): AttendanceRecord {
            $locked = AttendanceRecord::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();

            if ($locked->status === AttendanceRecordStatus::Voided) {
                return $locked;
            }

            $before = ['status' => $locked->status->value, 'voided_at' => $locked->voided_at?->toIso8601String()];

            $locked->fill([
                'status' => AttendanceRecordStatus::Voided,
                'voided_at' => CarbonImmutable::now(),
                'voided_by' => $actorUserId,
                'void_reason' => $reason,
            ])->save();

            $this->auditService->record(
                'attendance.record.void',
                $actorUserId,
                $locked,
                before: $before,
                after: ['status' => $locked->status->value, 'voided_at' => $locked->voided_at?->toIso8601String()],
                reason: $reason,
            );

            return $locked->refresh();
        });
    }

    public function correctTimes(
        AttendanceRecord $record,
        int $actorUserId,
        CarbonImmutable $checkedInAt,
        CarbonImmutable $checkedOutAt,
        string $reason,
    ): AttendanceRecord {
        $reason = trim($reason);
        if ($reason === '') {
            throw new DomainException('Manual correction reason is required.');
        }
        if ($checkedOutAt->lessThan($checkedInAt)) {
            throw new DomainException('Check-out cannot be before check-in.');
        }

        return DB::transaction(function () use ($record, $actorUserId, $checkedInAt, $checkedOutAt, $reason): AttendanceRecord {
            $locked = AttendanceRecord::query()->whereKey($record->getKey())->lockForUpdate()->firstOrFail();
            if ($locked->status === AttendanceRecordStatus::Voided) {
                throw new DomainException('Voided attendance records cannot be corrected.');
            }

            [$shiftStart, $shiftEnd] = $this->snapshotWindow($locked, $checkedInAt);
            $metrics = $this->calculationService->calculate(
                $checkedInAt,
                $checkedOutAt,
                $shiftStart,
                $shiftEnd,
                (int) $locked->late_grace_minutes_snapshot,
                (int) $locked->early_leave_grace_minutes_snapshot,
            );

            $before = $this->summary($locked);
            $locked->fill([
                'checked_in_at' => $checkedInAt,
                'checked_out_at' => $checkedOutAt,
                ...$metrics,
                'adjusted_at' => CarbonImmutable::now(),
            ])->save();

            $this->auditService->record(
                'attendance.record.manual_correct',
                $actorUserId,
                $locked,
                before: $before,
                after: $this->summary($locked),
                reason: $reason,
            );

            return $locked->refresh();
        });
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

    private function summary(AttendanceRecord $record): array
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
