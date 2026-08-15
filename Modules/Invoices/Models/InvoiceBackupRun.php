<?php

namespace Modules\Invoices\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceBackupRun extends Model
{
    protected $fillable = [
        'mode', 'status', 'recipient', 'files_count', 'emails_sent', 'bytes_total',
        'files', 'message', 'started_at', 'finished_at',
    ];

    protected $casts = [
        'files' => 'array',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
