<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class EmployeeProfile extends Model
{
    use SoftDeletes;

    protected $table = 'employee_profiles';

    protected $fillable = [
        'user_id',
        'employee_code',
        'department',
        'position',
        'joined_date',
        'work_phone',
        'work_email',
        'status',
        'note',
    ];

    protected $casts = [
        'joined_date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
