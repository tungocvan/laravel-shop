<?php

namespace Modules\Attendance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Account\Models\User;

class AttendanceShift extends Model
{
    protected $table = 'attendance_shifts';

    protected $fillable = [
        'name',
        'code',
        'start_time',
        'end_time',
        'late_grace_minutes',
        'early_leave_grace_minutes',
        'is_default',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'late_grace_minutes' => 'integer',
        'early_leave_grace_minutes' => 'integer',
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
