<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceSourceRecord extends Model
{
    public const CLASSIFICATIONS = [
        'UNCLASSIFIED',
        'GOODS',
        'SERVICE_EXPENSE',
        'MIXED',
    ];

    public const CLASSIFICATION_SCOPES = [
        'INVOICE',
        'SUPPLIER',
    ];

    protected $fillable = [
        'invoice_id',
        'provider',
        'source_version',
        'header_payload',
        'header_hash',
        'header_fetched_at',
        'detail_payload',
        'detail_hash',
        'detail_status',
        'detail_fetched_at',
        'last_error',
        'business_classification',
        'classification_scope',
        'business_note',
        'classified_by',
        'classified_at',
    ];

    protected $casts = [
        'header_payload' => 'array',
        'detail_payload' => 'array',
        'header_fetched_at' => 'datetime',
        'detail_fetched_at' => 'datetime',
        'classified_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }

    public function hasUsableDetail(): bool
    {
        return $this->detail_status === 'READY'
            && is_array($this->detail_payload)
            && is_array($this->detail_payload['hdhhdvu'] ?? null)
            && $this->detail_payload['hdhhdvu'] !== [];
    }
}
