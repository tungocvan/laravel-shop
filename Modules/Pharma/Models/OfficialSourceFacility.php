<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialSourceFacility extends Model
{
    protected $table = 'pharma_official_source_facilities';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'raw_payload' => 'array',
            'is_active' => 'boolean',
            'first_seen_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function lastSyncBatch(): BelongsTo
    {
        return $this->belongsTo(OfficialSourceSyncBatch::class, 'last_sync_batch_id');
    }
}
