<?php

namespace Modules\ClientPortal\Models;

use Illuminate\Database\Eloquent\Model;

class SyncRequest extends Model
{
    public $incrementing = false;

    protected $table = 'client_portal_sync_requests';

    protected $keyType = 'string';

    protected $guarded = [];

    protected $casts = [
        'source_ids' => 'array',
        'selected_count' => 'integer',
        'inserted_count' => 'integer',
        'duplicate_count' => 'integer',
        'missing_count' => 'integer',
        'started_at' => 'datetime',
        'finished_at' => 'datetime',
    ];
}
