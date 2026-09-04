<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KqlcntImportBatch extends Model
{
    protected $table = 'muasamcong_kqlcnt_import_batches';

    protected $guarded = [];

    protected $casts = [
        'headers' => 'array',
        'raw_rows' => 'array',
        'mapping' => 'array',
        'preview_rows' => 'array',
        'confirmed_at' => 'datetime',
    ];

    public function search(): BelongsTo
    {
        return $this->belongsTo(ContractorSearch::class, 'contractor_search_id');
    }
}
