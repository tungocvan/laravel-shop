<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class HeaderMenuItem extends Model
{
    protected $fillable = [
        'header_menu_id', 'parent_id', 'title', 'url',
        'route_name', 'params', 'icon', 'target',
        'sort_order', 'is_active'
    ];

    protected $casts = [
        'params' => 'array',
        'is_active' => 'boolean'
    ];

    public function children()
    {
        return $this->hasMany(HeaderMenuItem::class, 'parent_id')->orderBy('sort_order');
    }

    public function parent()
    {
        return $this->belongsTo(HeaderMenuItem::class, 'parent_id');
    }
}
