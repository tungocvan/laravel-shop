<?php

namespace Modules\Facebook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookConnection extends Model
{
    use SoftDeletes;

    protected $table = 'facebook_connections';

    protected $fillable = [
        'user_id',
        'facebook_user_id',
        'facebook_user_name',
        'user_access_token',
        'token_type',
        'token_expires_at',
        'granted_scopes',
        'declined_scopes',
        'status',
        'last_verified_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $hidden = [
        'user_access_token',
    ];

    protected $casts = [
        'user_access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'granted_scopes' => 'array',
        'declined_scopes' => 'array',
        'last_verified_at' => 'datetime',
    ];

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INVALID = 'invalid';

    public const STATUS_DISCONNECTED = 'disconnected';

    public function pages(): HasMany
    {
        return $this->hasMany(FacebookPage::class);
    }
}
