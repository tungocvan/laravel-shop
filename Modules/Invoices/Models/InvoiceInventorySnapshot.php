<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InvoiceInventorySnapshot extends Model
{
    protected $fillable = [
        'invoice_id',
        'source',
        'payload_hash',
        'status',
        'raw_payload',
        'attempt_count',
        'last_attempt_at',
        'fetched_at',
        'normalized_at',
        'last_error',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'last_attempt_at' => 'datetime',
        'fetched_at' => 'datetime',
        'normalized_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceInventoryStagingLine::class, 'snapshot_id');
    }
}
