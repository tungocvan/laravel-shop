<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class FooterLink extends Model
{
    protected $fillable = [
        'footer_column_id', 'label', 'url',
        'route_name', 'new_tab', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'new_tab' => 'boolean',
        'is_active' => 'boolean'
    ];
}
