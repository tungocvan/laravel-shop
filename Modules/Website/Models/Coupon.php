<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Coupon extends Model
{
    use HasFactory;

    protected $fillable = [
        'code', 'description', 'type', 'value', 
        'min_order_value', 'usage_limit', 'usage_count', 
        'starts_at', 'expires_at', 'is_active'
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    // Helper kiểm tra còn hạn không
    public function getIsValidAttribute()
    {
        if (!$this->is_active) return false;
        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) return false;
        if ($this->starts_at && now()->lt($this->starts_at)) return false;
        if ($this->expires_at && now()->gt($this->expires_at)) return false;
        
        return true;
    }
}