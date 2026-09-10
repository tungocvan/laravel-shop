<?php

namespace Modules\Inventory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvoiceInbox extends Model
{
    protected $table = 'inventory_invoice_inbox';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'issued_at_snapshot' => 'datetime',
            'received_at' => 'datetime',
            'last_seen_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function lines(): HasMany
    {
        return $this->hasMany(InvoiceInboxLine::class, 'inbox_id')->orderBy('line_number');
    }

    public function receipt(): BelongsTo
    {
        return $this->belongsTo(Receipt::class, 'receipt_id');
    }
}
