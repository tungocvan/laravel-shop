<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OfficialFacilityImportBatch extends Model
{
    protected $table = 'pharma_official_import_batches';

    protected $guarded = [];

    protected $casts = ['source_date' => 'date', 'started_at' => 'datetime', 'completed_at' => 'datetime', 'summary' => 'array'];

    public function rows(): HasMany
    {
        return $this->hasMany(OfficialFacilityImportRow::class, 'batch_id');
    }
}
