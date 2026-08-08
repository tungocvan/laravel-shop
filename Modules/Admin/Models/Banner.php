<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Banner extends Model
{
    use HasFactory;

    // 1. KHOI_TAO_MODEL_BANNER
    protected $table = 'wp_banners';

    protected $fillable = [
        'title',
        'sub_title', // Mới
        'btn_text',  // Mới
        'image_desktop',
        'image_mobile',
        'link',
        'position',
        'order',
        'is_active',
    ];
    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
    ];

    // Scope lấy banner active theo vị trí
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopePosition($query, $position)
    {
        return $query->where('position', $position)->orderBy('order', 'asc');
    }
    // End 1.
}
