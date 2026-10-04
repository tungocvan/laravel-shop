<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InventoryReceiptDocumentShare extends Model
{
    protected $table = 'pharma_inventory_receipt_document_shares';

    protected $fillable = [
        'document_id','created_by','token_hash','token_encrypted','expires_at','revoked_at',
    ];

    protected $casts = ['expires_at'=>'datetime','revoked_at'=>'datetime'];

    public function document(): BelongsTo
    {
        return $this->belongsTo(InventoryReceiptDocument::class, 'document_id');
    }

    public function isAvailable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
