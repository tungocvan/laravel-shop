<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class InventoryReceiptDocument extends Model
{
    protected $table = 'pharma_inventory_receipt_documents';

    protected $fillable = [
        'receipt_id','profile','disk','storage_path','download_name',
        'source_hash','generated_by','generated_at',
    ];

    protected $casts = ['generated_at' => 'datetime'];

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(InventoryReceipt::class, 'receipt_id');
    }

    public function shares(): HasMany
    {
        return $this->hasMany(InventoryReceiptDocumentShare::class, 'document_id');
    }
}
