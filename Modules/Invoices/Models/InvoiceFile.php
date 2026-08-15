<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceFile extends Model
{
    protected $table = 'invoice_files';

    protected $fillable = [
        'invoice_id',
        'provider',
        'status',
        'path',
        'size',
        'last_error',
        'downloaded_at',
    ];

    protected $casts = [
        'size' => 'integer',
        'downloaded_at' => 'datetime',
    ];

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoices::class, 'invoice_id');
    }
}
