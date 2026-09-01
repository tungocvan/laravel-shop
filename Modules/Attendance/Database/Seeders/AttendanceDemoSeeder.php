<?php

namespace Modules\Attendance\Database\Seeders;

use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Modules\Account\Models\EmployeeProfile;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Enums\VerificationResult;
use Modules\Attendance\Models\AttendanceLocation;
use Modules\Attendance\Models\AttendanceRecord;
use Modules\Attendance\Models\AttendanceShift;
use RuntimeException;

class AttendanceDemoSeeder extends Seeder
{
    public function run(): void
    {
        $shift = AttendanceShift::query()->where('code', 'DEFAULT')->first();

        if (! $shift) {
            throw new RuntimeException('Attendance default shift is missing. Run AttendanceDefaultsSeeder first.');
        }

        $employees = EmployeeProfile::query()
            ->whereNotNull('user_id')
            ->with('user')
            ->orderBy('id')
            ->limit(8)
            ->get()
            ->filter(fn (EmployeeProfile $profile) => $profile->user !== null)
            ->values();

        if ($employees->isEmpty()) {
            throw new RuntimeException('No Account employee profiles with users are available for Attendance demo data.');
        }

        $location = AttendanceLocation::query()->updateOrCreate(
            ['code' => 'DEMO-HQ'],
            [
                'name' => 'Văn phòng chính (Demo)',
                'latitude' => 10.7768890,
                'longitude' => 106.7008060,
                'radius_meters' => 150,
                'maximum_accuracy_meters' => 100,
                'is_active' => true,
                'check_in_enabled' => true,
                'check_out_enabled' => true,
            ],
        );

        $today = CarbonImmutable::today();
        $patterns = [
            ['in' => '07:56', 'out' => '17:04', 'status' => AttendanceRecordStatus::Completed, 'late' => 0, 'early' => 0],
            ['in' => '08:13', 'out' => '17:03', 'status' => AttendanceRecordStatus::Completed, 'late' => 8, 'early' => 0],
            ['in' => '07:59', 'out' => '16:42', 'status' => AttendanceRecordStatus::Completed, 'late' => 0, 'early' => 13],
            ['in' => '08:18', 'out' => '16:40', 'status' => AttendanceRecordStatus::Completed, 'late' => 13, 'early' => 15],
            ['in' => '08:02', 'out' => null, 'status' => AttendanceRecordStatus::CheckedIn, 'late' => 0, 'early' => 0],
            ['in' => '08:09', 'out' => '17:02', 'status' => AttendanceRecordStatus::Voided, 'late' => 4, 'early' => 0],
        ];

        for ($i = 0; $i < 24; $i++) {
            /** @var EmployeeProfile $employee */
            $employee = $employees[$i % $employees->count()];
            $pattern = $patterns[$i % count($patterns)];
            $workDate = $today->subDays($i % 12);
            $checkedInAt = CarbonImmutable::parse($workDate->toDateString().' '.$pattern['in']);
            $checkedOutAt = $pattern['out']
                ? CarbonImmutable::parse($workDate->toDateString().' '.$pattern['out'])
                : null;

            $workedMinutes = $checkedOutAt ? $checkedInAt->diffInMinutes($checkedOutAt) : 0;
            $sessionKey = sprintf('demo-export-%s-%d-%02d', $workDate->format('Ymd'), $employee->id, $i);

            AttendanceRecord::query()->updateOrCreate(
                ['session_key' => $sessionKey],
                [
                    'employee_profile_id' => $employee->id,
                    'user_id' => $employee->user_id,
                    'work_date' => $workDate->toDateString(),
                    'shift_id' => $shift->id,
                    'status' => $pattern['status']->value,
                    'shift_code_snapshot' => $shift->code,
                    'shift_name_snapshot' => $shift->name,
                    'shift_start_time_snapshot' => $shift->start_time,
                    'shift_end_time_snapshot' => $shift->end_time,
                    'late_grace_minutes_snapshot' => $shift->late_grace_minutes,
                    'early_leave_grace_minutes_snapshot' => $shift->early_leave_grace_minutes,
                    'checked_in_at' => $checkedInAt,
                    'check_in_location_id' => $location->id,
                    'check_in_latitude' => 10.7768890,
                    'check_in_longitude' => 106.7008060,
                    'check_in_accuracy_meters' => 12,
                    'check_in_distance_meters' => 8,
                    'check_in_captured_at' => $checkedInAt,
                    'check_in_verification_result' => VerificationResult::Verified->value,
                    'checked_out_at' => $checkedOutAt,
                    'check_out_location_id' => $checkedOutAt ? $location->id : null,
                    'check_out_latitude' => $checkedOutAt ? 10.7768890 : null,
                    'check_out_longitude' => $checkedOutAt ? 106.7008060 : null,
                    'check_out_accuracy_meters' => $checkedOutAt ? 10 : null,
                    'check_out_distance_meters' => $checkedOutAt ? 6 : null,
                    'check_out_captured_at' => $checkedOutAt,
                    'check_out_verification_result' => $checkedOutAt ? VerificationResult::Verified->value : null,
                    'worked_minutes' => $workedMinutes,
                    'late_minutes' => $pattern['late'],
                    'early_leave_minutes' => $pattern['early'],
                    'voided_at' => $pattern['status'] === AttendanceRecordStatus::Voided ? $checkedOutAt : null,
                    'void_reason' => $pattern['status'] === AttendanceRecordStatus::Voided ? 'Dữ liệu demo cho kiểm thử export' : null,
                ],
            );
        }
    }
}
