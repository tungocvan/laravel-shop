<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Account\Models\EmployeeProfile;
use Modules\Account\Models\User;
use Modules\Attendance\Enums\AttendanceRecordStatus;
use Modules\Attendance\Enums\VerificationResult;

class AttendanceRecord extends Model
{
    protected $table = 'attendance_records';

    protected $fillable = [
        'employee_profile_id',
        'user_id',
        'work_date',
        'shift_id',
        'session_key',
        'status',
        'shift_code_snapshot',
        'shift_name_snapshot',
        'shift_start_time_snapshot',
        'shift_end_time_snapshot',
        'late_grace_minutes_snapshot',
        'early_leave_grace_minutes_snapshot',
        'checked_in_at',
        'check_in_location_id',
        'check_in_latitude',
        'check_in_longitude',
        'check_in_accuracy_meters',
        'check_in_distance_meters',
        'check_in_captured_at',
        'check_in_verification_result',
        'checked_out_at',
        'check_out_location_id',
        'check_out_latitude',
        'check_out_longitude',
        'check_out_accuracy_meters',
        'check_out_distance_meters',
        'check_out_captured_at',
        'check_out_verification_result',
        'worked_minutes',
        'late_minutes',
        'early_leave_minutes',
        'voided_at',
        'voided_by',
        'void_reason',
        'adjusted_at',
    ];

    protected $casts = [
        'work_date' => 'date',
        'status' => AttendanceRecordStatus::class,
        'checked_in_at' => 'datetime',
        'check_in_captured_at' => 'datetime',
        'check_in_verification_result' => VerificationResult::class,
        'checked_out_at' => 'datetime',
        'check_out_captured_at' => 'datetime',
        'check_out_verification_result' => VerificationResult::class,
        'worked_minutes' => 'integer',
        'late_minutes' => 'integer',
        'early_leave_minutes' => 'integer',
        'voided_at' => 'datetime',
        'adjusted_at' => 'datetime',
    ];

    public function employeeProfile(): BelongsTo
    {
        return $this->belongsTo(EmployeeProfile::class, 'employee_profile_id')->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id')->withTrashed();
    }

    public function shift(): BelongsTo
    {
        return $this->belongsTo(AttendanceShift::class, 'shift_id');
    }

    public function checkInLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_in_location_id');
    }

    public function checkOutLocation(): BelongsTo
    {
        return $this->belongsTo(AttendanceLocation::class, 'check_out_location_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by')->withTrashed();
    }

    public function adjustmentRequests(): HasMany
    {
        return $this->hasMany(AttendanceAdjustmentRequest::class, 'attendance_record_id');
    }

    public function auditEvents(): HasMany
    {
        return $this->hasMany(AttendanceAuditEvent::class, 'attendance_record_id');
    }
}
