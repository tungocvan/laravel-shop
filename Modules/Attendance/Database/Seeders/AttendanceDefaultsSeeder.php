<?php

namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Attendance\Models\AttendanceShift;

class AttendanceDefaultsSeeder extends Seeder
{
    public function run(): void
    {
        AttendanceShift::query()->updateOrCreate(
            ['code' => 'DEFAULT'],
            [
                'name' => 'Ca mặc định',
                'start_time' => config('attendance.attendance.shift.start_time', '08:00'),
                'end_time' => config('attendance.attendance.shift.end_time', '17:00'),
                'late_grace_minutes' => config('attendance.attendance.shift.late_grace_minutes', 5),
                'early_leave_grace_minutes' => config('attendance.attendance.shift.early_leave_grace_minutes', 5),
                'is_default' => true,
                'is_active' => true,
            ],
        );
    }
}
