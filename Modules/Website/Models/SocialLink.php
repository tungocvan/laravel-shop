<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class SocialLink extends Model
{
    protected $fillable = [
        'platform', 'name', 'url', 'icon_class',
        'bg_color', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];
}
