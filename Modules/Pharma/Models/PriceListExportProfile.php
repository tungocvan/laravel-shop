<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListExportProfile extends Model
{
    protected $table = 'pharma_price_list_export_profiles';

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
        'header_footer' => 'array',
        'page_setup' => 'array',
    ];
}
