<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\User;

class AttendanceAuditEvent extends Model
{
    public $timestamps = false;

    protected $table = 'attendance_audit_events';

    protected $fillable = [
        'actor_user_id',
        'action',
        'target_type',
        'target_id',
        'attendance_record_id',
        'reason',
        'before_json',
        'after_json',
        'metadata_json',
        'created_at',
    ];

    protected $casts = [
        'before_json' => 'array',
        'after_json' => 'array',
        'metadata_json' => 'array',
        'created_at' => 'datetime',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id')->withTrashed();
    }

    public function attendanceRecord(): BelongsTo
    {
        return $this->belongsTo(AttendanceRecord::class, 'attendance_record_id');
    }
}
