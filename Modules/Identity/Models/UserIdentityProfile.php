<?php

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class UserIdentityProfile extends Model
{
    use SoftDeletes;

    protected $table = 'user_identity_profiles';

    protected $fillable = [
        'user_id',
        'identity_type',
        'identity_number',
        'issued_date',
        'issued_place',
        'front_image',
        'back_image',
        'portrait_4x6_image',
        'tax_code',
        'tax_registered_name',
        'tax_address',
        'note',
    ];

    protected $casts = [
        'issued_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
