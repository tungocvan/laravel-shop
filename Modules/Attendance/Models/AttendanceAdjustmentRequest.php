<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\EmployeeProfile;
use Modules\Account\Models\User;
use Modules\Attendance\Enums\AdjustmentStatus;

class AttendanceAdjustmentRequest extends Model
{
    protected $table = 'attendance_adjustment_requests';

    protected $fillable = [
        'employee_profile_id',
        'user_id',
        'attendance_record_id',
        'requested_work_date',
        'requested_check_in_at',
        'requested_check_out_at',
        'reason',
        'note',
        'status',
        'submitted_at',
        'reviewed_at',
        'reviewed_by',
        'review_note',
    ];

    protected $casts = [
        'requested_work_date' => 'date',
        'requested_check_in_at' => 'datetime',
        'requested_check_out_at' => 'datetime',
        'status' => AdjustmentStatus::class,
        'submitted_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by')->withTrashed();
    }
}
