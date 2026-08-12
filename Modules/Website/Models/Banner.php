<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

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
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'order' => 'integer',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    // Scope lấy banner active theo vị trí
    public function scopeActive($query)
    {
        return $query->where('is_active', true)
            ->where(fn ($schedule) => $schedule->whereNull('starts_at')->orWhere('starts_at', '<=', now()))
            ->where(fn ($schedule) => $schedule->whereNull('ends_at')->orWhere('ends_at', '>=', now()));
    }

    public function getScheduleStatusAttribute(): string
    {
        if (! $this->is_active) {
            return 'inactive';
        }
        if ($this->starts_at?->isFuture()) {
            return 'scheduled';
        }
        if ($this->ends_at?->isPast()) {
            return 'expired';
        }

        return 'active';
    }

    public function scopePosition($query, $position)
    {
        return $query->where('position', $position)->orderBy('order', 'asc');
    }
    // End 1.
}
