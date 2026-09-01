<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use DomainException;
use Modules\Attendance\Models\AttendanceShift;

class ShiftResolver
{
    public function resolve(CarbonImmutable $now): array
    {
        $shifts = AttendanceShift::query()
            ->where('is_active', true)
            ->where('is_default', true)
            ->get();

        if ($shifts->isEmpty()) {
            throw new DomainException('Attendance requires one active default shift.');
        }

        if ($shifts->count() !== 1) {
            throw new DomainException('Attendance has multiple active default shifts.');
        }

        /** @var AttendanceShift $shift */
        $shift = $shifts->first();

        return $this->resolveFromShift($shift, $now);
    }

    public function resolveFromShift(AttendanceShift $shift, CarbonImmutable $now): array
    {
        $startToday = CarbonImmutable::parse($now->format('Y-m-d').' '.$shift->start_time, $now->getTimezone());
        $endToday = CarbonImmutable::parse($now->format('Y-m-d').' '.$shift->end_time, $now->getTimezone());
        $overnight = $endToday->lessThanOrEqualTo($startToday);

        if ($overnight && $now->lt($endToday)) {
            $start = $startToday->subDay();
            $end = $endToday;
        } else {
            $start = $startToday;
            $end = $overnight ? $endToday->addDay() : $endToday;
        }

        return [
            'shift' => $shift,
            'work_date' => $start->toDateString(),
            'starts_at' => $start,
            'ends_at' => $end,
            'snapshot' => [
                'shift_id' => $shift->getKey(),
                'shift_code_snapshot' => $shift->code,
                'shift_name_snapshot' => $shift->name,
                'shift_start_time_snapshot' => $shift->start_time,
                'shift_end_time_snapshot' => $shift->end_time,
                'late_grace_minutes_snapshot' => (int) $shift->late_grace_minutes,
                'early_leave_grace_minutes_snapshot' => (int) $shift->early_leave_grace_minutes,
            ],
        ];
    }
}
