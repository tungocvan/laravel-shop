<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;

class FooterColumn extends Model
{
    protected $fillable = ['title', 'slug', 'sort_order', 'is_active'];

    public function links()
    {
        // Quan hệ 1-N: Một cột có nhiều links
        return $this->hasMany(FooterLink::class, 'footer_column_id');
    }
}
