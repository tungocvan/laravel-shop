<?php

namespace Modules\Attendance\Services;

use Carbon\CarbonImmutable;
use Modules\Attendance\Enums\AdjustmentStatus;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Models\AttendanceAdjustmentRequest;
use Modules\Attendance\Models\AttendanceRecord;

class AttendanceDashboardService
{
    public function summary(?CarbonImmutable $today = null): array
    {
        $today ??= CarbonImmutable::today();
        $date = $today->toDateString();
        $records = AttendanceRecord::query()->whereDate('work_date', $date);

        return [
            'date' => $date,
            'checked_in' => (clone $records)->where('status', AttendanceRecordStatus::CheckedIn->value)->count(),
            'completed' => (clone $records)->where('status', AttendanceRecordStatus::Completed->value)->count(),
            'late' => (clone $records)->where('late_minutes', '>', 0)->count(),
            'early_leave' => (clone $records)->where('early_leave_minutes', '>', 0)->count(),
            'missing_checkout' => (clone $records)
                ->where('status', AttendanceRecordStatus::CheckedIn->value)
                ->whereNotNull('checked_in_at')
                ->whereNull('checked_out_at')
                ->count(),
            'pending_adjustments' => AttendanceAdjustmentRequest::query()
                ->where('status', AdjustmentStatus::Pending->value)
                ->count(),
            'recent' => AttendanceRecord::query()
                ->with(['employeeProfile', 'user'])
                ->latest('updated_at')
                ->limit(5)
                ->get(),
        ];
    }
}
