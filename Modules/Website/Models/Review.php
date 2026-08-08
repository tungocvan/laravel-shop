<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class Review extends Model
{
    protected $table = 'reviews';

    protected $fillable = [
        'user_id',
        'product_id',
        'rating',
        'comment',
        'images',
        'is_approved',
        'is_verified_purchase',
        'likes'
    ];

    protected $casts = [
        'images' => 'array', // Tự động chuyển JSON sang Mảng
        'is_approved' => 'boolean',
        'is_verified_purchase' => 'boolean',
    ];

    // Quan hệ: Đánh giá thuộc về 1 User
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Quan hệ: Đánh giá thuộc về 1 Sản phẩm
    public function product()
    {
        return $this->belongsTo(WpProduct::class, 'product_id');
    }
}
