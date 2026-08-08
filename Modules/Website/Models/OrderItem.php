<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OrderItem extends Model
{
    protected $fillable = [
        'order_id', 'product_id',
        'product_name', 'price', 'quantity', 'total', 'options', // <--- Thêm options
        'commission_rate',   // <--- MỚI: % hoa hồng lúc mua
        'commission_amount'  // <--- MỚI: Tiền hoa hồng của item này
    ];

    // Tự động chuyển JSON sang Mảng khi lấy ra
    protected $casts = [
        'options' => 'array',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:2',
    ];

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function items()
    {
        return $this->hasMany(OrderItem::class, 'order_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(WpProduct::class, 'product_id');
    }
}
