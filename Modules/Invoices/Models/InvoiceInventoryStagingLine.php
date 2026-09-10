<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

final class InvoiceInventoryStagingLine extends Model
{
    protected $fillable = [
        'snapshot_id', 'line_number', 'source_line_key', 'raw_description', 'source_product_code',
        'source_quantity', 'source_uom', 'unit_price', 'line_amount', 'tax_rate', 'normalized_name', 'strength',
        'dosage_form', 'package_spec', 'manufacturer', 'normalized_uom', 'lot_number',
        'manufacture_date', 'expiry_date', 'normalization_status', 'raw_payload', 'normalization_meta',
    ];

    protected $casts = [
        'raw_payload' => 'array',
        'normalization_meta' => 'array',
        'manufacture_date' => 'date',
        'expiry_date' => 'date',
        'source_quantity' => 'decimal:4',
        'unit_price' => 'decimal:4',
        'line_amount' => 'decimal:4',
    ];

    public function snapshot(): BelongsTo
    {
        return $this->belongsTo(InvoiceInventorySnapshot::class, 'snapshot_id');
    }
}
