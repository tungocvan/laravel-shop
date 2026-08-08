<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use SoftDeletes;

    protected $table = 'customer_profiles';

    protected $fillable = [
        'user_id',
        'customer_code',
        'gender',
        'birthday',
        'address',
        'province',
        'district',
        'ward',
        'status',
        'note',
    ];

    protected $casts = [
        'birthday' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
