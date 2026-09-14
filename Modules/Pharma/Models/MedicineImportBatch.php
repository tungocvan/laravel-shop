<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MedicineImportBatch extends Model
{
    public const STATUS_STAGED = 'staged';

    public const STATUS_READY = 'ready';

    public const STATUS_COMMITTED = 'committed';

    public const STATUS_FAILED = 'failed';

    protected $table = 'pharma_medicine_import_batches';

    protected $guarded = [];

    protected $casts = [
        'committed_at' => 'datetime',
    ];

    public function rows(): HasMany
    {
        return $this->hasMany(MedicineImportRow::class, 'batch_id');
    }
}
