<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Website\Models\WpProduct;
class FlashSaleItem extends Model
{
    // 3. KHOI_TAO_MODEL_FLASHSALE_ITEM
    protected $table = 'wp_flash_sale_items';

    public $timestamps = false; // Bảng pivot thường không cần timestamps

    protected $fillable = [
        'flash_sale_id',
        'product_id',
        'price',
        'quantity',
        'sold',
    ];

    // Link ngược lại Flash Sale cha
    public function flashSale(): BelongsTo
    {
        return $this->belongsTo(FlashSale::class, 'flash_sale_id');
    }

    // Link sang Product (Giả định bạn có Model Product trong Admin, nếu chưa có thì trỏ tạm namespace này)
    public function product(): BelongsTo
    {
        // LƯU Ý: Namespace Product này có thể thay đổi tùy cấu trúc module Product của bạn
        return $this->belongsTo(WpProduct::class, 'product_id');
    }
    // End 3.
}
