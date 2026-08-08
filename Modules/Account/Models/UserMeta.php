<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;


class UserMeta extends Model
{
    protected $table = 'user_metas';

    protected $fillable = [
        'user_id',
        'key',
        'value',
        'group_name',
        'type',
        'label',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
