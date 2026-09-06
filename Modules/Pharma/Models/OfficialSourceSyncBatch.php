<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialSourceSyncBatch extends Model
{
    protected $table = 'pharma_official_source_sync_batches';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    public function facilities(): HasMany
    {
        return $this->hasMany(OfficialSourceFacility::class, 'last_sync_batch_id');
    }
}
