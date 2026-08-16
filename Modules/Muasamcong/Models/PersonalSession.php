<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class PersonalSession extends Model
{
    protected $table = 'muasamcong_personal_sessions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'verified_at' => 'datetime',
            'last_failed_at' => 'datetime',
        ];
    }
}
