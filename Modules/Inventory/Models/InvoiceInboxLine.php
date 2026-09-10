<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceInboxLine extends Model
{
    protected $table = 'inventory_invoice_inbox_lines';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'expiry_date' => 'date',
            'manufacture_date' => 'date',
            'metadata' => 'array',
        ];
    }

    public function inbox(): BelongsTo
    {
        return $this->belongsTo(InvoiceInbox::class, 'inbox_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(InventoryItem::class, 'inventory_item_id');
    }
}
