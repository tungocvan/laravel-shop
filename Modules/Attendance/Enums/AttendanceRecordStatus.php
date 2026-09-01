<?php

namespace Modules\Attendance\Enums;

enum AttendanceRecordStatus: string
{
    case CheckedIn = 'checked_in';
    case Completed = 'completed';
    case Voided = 'voided';
}
