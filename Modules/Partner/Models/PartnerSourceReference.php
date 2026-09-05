<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSourceReference extends Model
{
    protected $fillable = [
        'partner_id', 'source', 'external_id', 'source_province_code', 'source_date',
        'first_seen_at', 'last_seen_at', 'metadata',
    ];

    protected $casts = [
        'source_date' => 'date',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
        'metadata' => 'array',
    ];

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class);
    }
}
