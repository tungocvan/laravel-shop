<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Partner\Models\Partner;

class OfficialFacilityImportRow extends Model
{
    protected $table = 'pharma_official_import_rows';

    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array', 'validation_errors' => 'array', 'match_context' => 'array',
        'is_selected' => 'boolean', 'resolved_at' => 'datetime', 'imported_at' => 'datetime',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(OfficialFacilityImportBatch::class, 'batch_id');
    }

    public function matchedPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'matched_partner_id');
    }
}
