<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserAddress extends Model
{
    use HasFactory;

    protected $table = 'user_addresses';

    protected $fillable = [
        'user_id',
        'name',       // Tên người nhận
        'phone',      // SĐT người nhận
        'address',    // Địa chỉ chi tiết (Số nhà, đường)
        'city',       // Tỉnh/Thành phố
        'district',   // Quận/Huyện (nếu có dùng)
        'ward',       // Phường/Xã (nếu có dùng)
        'is_default', // Trạng thái mặc định
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Quan hệ: Thuộc về một User
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Helper: Lấy chuỗi địa chỉ đầy đủ
     * VD: 123 Đường A, Phường B, Quận C, TP.HCM
     */
    public function getFullAddressAttribute()
    {
        // Gom các thành phần địa chỉ vào mảng
        $parts = [
            $this->address,
            $this->ward,
            $this->district,
            $this->city
        ];

        // Lọc bỏ các giá trị rỗng/null và nối lại bằng dấu phẩy
        return implode(', ', array_filter($parts));
    }
}
