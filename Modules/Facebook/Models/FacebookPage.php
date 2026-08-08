<?php

namespace Modules\Facebook\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class FacebookPage extends Model
{
    use SoftDeletes;

    protected $table = 'facebook_pages';

    protected $fillable = [
        'facebook_connection_id',
        'page_id',
        'page_name',
        'page_category',
        'page_picture_url',
        'page_access_token',
        'token_expires_at',
        'granted_tasks',
        'is_active',
        'is_default',
        'last_synced_at',
        'last_verified_at',
        'last_error_code',
        'last_error_message',
    ];

    protected $hidden = [
        'page_access_token',
    ];

    protected $casts = [
        'page_access_token' => 'encrypted',
        'token_expires_at' => 'datetime',
        'granted_tasks' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'last_synced_at' => 'datetime',
        'last_verified_at' => 'datetime',
    ];

    public function connection(): BelongsTo
    {
        return $this->belongsTo(FacebookConnection::class, 'facebook_connection_id');
    }

    public function posts(): HasMany
    {
        return $this->hasMany(FacebookPost::class);
    }

    public function getMaskedPageAccessTokenAttribute(): string
    {
        $token = $this->page_access_token;

        if (! $token) {
            return '-';
        }

        if (strlen($token) <= 12) {
            return substr($token, 0, 2).str_repeat('*', max(4, strlen($token) - 4)).substr($token, -2);
        }

        return substr($token, 0, 4).str_repeat('*', 14).substr($token, -4);
    }
}
