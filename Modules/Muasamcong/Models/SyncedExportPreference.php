<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedExportPreference extends Model
{
    protected $table = 'muasamcong_synced_export_preferences';

    protected $guarded = [];

    protected $casts = [
        'column_order' => 'array',
        'selected_columns' => 'array',
        'alignments' => 'array',
        'widths' => 'array',
    ];
}
