<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FlashSale extends Model
{
    // 2. KHOI_TAO_MODEL_FLASHSALE
    protected $table = 'wp_flash_sales';

    protected $fillable = [
        'title',
        'start_time',
        'end_time',
        'is_active',
    ];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Relation đến bảng items
    public function items(): HasMany
    {
        return $this->hasMany(FlashSaleItem::class, 'flash_sale_id');
    }

    // Check xem Flash Sale có đang chạy không
    public function getIsRunningAttribute()
    {
        $now = now();
        return $this->is_active && $this->start_time <= $now && $this->end_time >= $now;
    }
    // End 2.
}
