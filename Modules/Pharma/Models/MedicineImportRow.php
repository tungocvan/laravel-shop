<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MedicineImportRow extends Model
{
    public const CLASS_NEW = 'new';

    public const CLASS_UPDATE = 'update';

    public const CLASS_DUPLICATE = 'duplicate';

    public const CLASS_CONFLICT = 'conflict';

    public const CLASS_NEEDS_REVIEW = 'needs_review';

    protected $table = 'pharma_medicine_import_rows';

    protected $guarded = [];

    protected $casts = [
        'raw_payload' => 'array',
        'normalized_payload' => 'array',
        'selected' => 'boolean',
    ];

    public function batch(): BelongsTo
    {
        return $this->belongsTo(MedicineImportBatch::class, 'batch_id');
    }

    public function matchedMedicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'matched_medicine_id');
    }

    public function matchedVariant(): BelongsTo
    {
        return $this->belongsTo(MedicineVariant::class, 'matched_variant_id');
    }
}
