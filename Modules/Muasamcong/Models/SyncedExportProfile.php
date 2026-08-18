<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class SyncedExportProfile extends Model
{
    protected $table = 'muasamcong_synced_export_profiles';

    protected $guarded = [];

    protected $casts = [
        'is_default' => 'boolean',
        'column_order' => 'array',
        'selected_columns' => 'array',
        'headers' => 'array',
        'alignments' => 'array',
        'widths' => 'array',
        'data_types' => 'array',
        'decimals' => 'array',
    ];
}
