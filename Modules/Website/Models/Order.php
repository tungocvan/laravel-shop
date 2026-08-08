<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;
use Modules\Website\Models\OrderHistory;
//use Illuminate\Support\Facades\Http;

class Order extends Model
{
    protected $table = 'wp_orders';

    protected $fillable = [
        'user_id',
        'affiliate_id',       // <--- Mới
        'commission_status',  // <--- Mới
        'commission_amount',  // <--- Mới
        'order_code',
        'customer_name',
        'customer_phone',
        'customer_email',
        'customer_address',
        'note',
        'subtotal',
        'shipping_fee',
        'discount',
        'total',
        'payment_method',
        'rejection_reason',
        'status',
    ];

    // protected static function booted()
    // {
    //     static::created(function (Order $order) {

    //         Http::post(config('services.socket.url') . '/broadcast', [
    //             'channel' => 'orders',
    //             'event'   => 'order.created',
    //             'data'    => $order->toArray(),
    //         ]);

    //     });
    // }
    // Helper: Badge màu trạng thái (Master UI Style)
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            'pending' => '<span class="inline-flex items-center rounded-md bg-yellow-50 px-2 py-1 text-xs font-medium text-yellow-800 ring-1 ring-inset ring-yellow-600/20">Chờ xử lý</span>',
            'processing' => '<span class="inline-flex items-center rounded-md bg-blue-50 px-2 py-1 text-xs font-medium text-blue-700 ring-1 ring-inset ring-blue-700/10">Đang xử lý</span>',
            'shipping' => '<span class="inline-flex items-center rounded-md bg-indigo-50 px-2 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-700/10">Đang giao</span>',
            'completed' => '<span class="inline-flex items-center rounded-md bg-green-50 px-2 py-1 text-xs font-medium text-green-700 ring-1 ring-inset ring-green-600/20">Hoàn thành</span>',
            'cancelled' => '<span class="inline-flex items-center rounded-md bg-red-50 px-2 py-1 text-xs font-medium text-red-700 ring-1 ring-inset ring-red-600/10">Đã hủy</span>',
            default => '<span class="inline-flex items-center rounded-md bg-gray-50 px-2 py-1 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-500/10">Không rõ</span>',
        };
    }

    // Helper: Tên phương thức thanh toán
    public function getPaymentMethodLabelAttribute()
    {
        return match($this->payment_method) {
            'cod' => 'Thanh toán khi nhận hàng (COD)',
            'bank_transfer' => 'Chuyển khoản ngân hàng',
            'vnpay' => 'VNPAY',
            default => $this->payment_method,
        };
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    // Quan hệ với người giới thiệu (Affiliate)
    public function affiliate()
    {
        return $this->belongsTo(\App\Models\User::class, 'affiliate_id');
    }
    public function histories()
    {
        return $this->hasMany(OrderHistory::class)->orderBy('created_at', 'desc'); // Mới nhất lên đầu
    }
    // Thêm phương thức để tính toán lại tổng hoa hồng từ các items con
    public function recalculateTotalCommission(): float
    {
        // Sum toàn bộ commission_amount của các item thuộc order này
        $total = $this->items()->sum('commission_amount');

        $this->update(['commission_amount' => $total]);

        return $total;
    }
}
