<?php

namespace Modules\ClientPortal\Applications\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class PublicShare extends Model
{
    protected $table = 'client_portal_public_shares';

    protected $guarded = [];

    protected $casts = [
        'payload' => 'array',
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_viewed_at' => 'datetime',
    ];

    public function isAvailable(): bool
    {
        if ($this->revoked_at !== null) {
            return false;
        }

        return $this->expires_at === null || $this->expires_at->isFuture();
    }
}
