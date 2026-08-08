<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class HeaderMenu extends Model
{
    protected $fillable = ['name', 'location', 'is_active'];

    public function items()
    {
        return $this->hasMany(HeaderMenuItem::class)->orderBy('sort_order');
    }

    // Chỉ lấy menu root (cấp 1)
    public function rootItems()
    {
        return $this->items()->whereNull('parent_id')->with('children');
    }
}
