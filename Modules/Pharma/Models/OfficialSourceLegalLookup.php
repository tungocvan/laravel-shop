<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficialSourceLegalLookup extends Model
{
    protected $table = 'pharma_official_source_legal_lookups';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'candidates' => 'array',
            'requested_at' => 'datetime',
            'completed_at' => 'datetime',
            'reviewed_at' => 'datetime',
        ];
    }

    public function facility(): BelongsTo
    {
        return $this->belongsTo(OfficialSourceFacility::class, 'facility_id');
    }
}
