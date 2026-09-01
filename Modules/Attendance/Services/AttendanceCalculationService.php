<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class AttendanceCalculationService
{
    public function calculate(
        CarbonImmutable $checkedInAt,
        CarbonImmutable $checkedOutAt,
        CarbonImmutable $shiftStartsAt,
        CarbonImmutable $shiftEndsAt,
        int $lateGraceMinutes,
        int $earlyLeaveGraceMinutes,
    ): array {
        if ($checkedOutAt->lt($checkedInAt)) {
            throw new InvalidArgumentException('Check-out cannot be earlier than check-in.');
        }

        $workedMinutes = $checkedInAt->diffInMinutes($checkedOutAt);
        $lateBoundary = $shiftStartsAt->addMinutes(max(0, $lateGraceMinutes));
        $earlyBoundary = $shiftEndsAt->subMinutes(max(0, $earlyLeaveGraceMinutes));

        $lateMinutes = $checkedInAt->gt($lateBoundary)
            ? $shiftStartsAt->diffInMinutes($checkedInAt)
            : 0;

        $earlyLeaveMinutes = $checkedOutAt->lt($earlyBoundary)
            ? $checkedOutAt->diffInMinutes($shiftEndsAt)
            : 0;

        return [
            'worked_minutes' => $workedMinutes,
            'late_minutes' => $lateMinutes,
            'early_leave_minutes' => $earlyLeaveMinutes,
        ];
    }
}
