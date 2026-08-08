<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany; // <--- Nhớ import dòng này
use Modules\Website\Models\Wishlist; // <--- Import Model Wishlist
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
use App\Models\User;

class WpProduct extends Model
{
    protected $table = 'wp_products'; // Khóa cứng tên bảng

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'description',
        'regular_price',
        'sale_price',
        'quantity',      // <--- Đã thêm
        'sold_count',    // <--- Đã thêm
        'image',
        'gallery',
        'tags',
        'is_active',
        'is_featured',   // <--- Đã thêm
        'user_id',       // <--- Đã thêm
        'views',          // <--- Đã thêm
        'affiliate_commission_rate'
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'gallery' => 'array',
        'tags' => 'array',
        'regular_price' => 'decimal:2',
        'sale_price' => 'decimal:2',
        'affiliate_commission_rate' => 'decimal:2',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
    */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'category_product', 'product_id', 'category_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /*
    |--------------------------------------------------------------------------
    | Accessors
    |--------------------------------------------------------------------------
    */
    // Giá bán cuối cùng (ưu tiên sale_price)
    protected function finalPrice(): Attribute
    {
        return Attribute::make(
            get: fn () => $this->sale_price && $this->sale_price < $this->regular_price
                ? $this->sale_price
                : $this->regular_price
        );
    }

    // Phần trăm giảm giá
    protected function discountPercent(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (!$this->sale_price || !$this->regular_price || $this->sale_price >= $this->regular_price) {
                    return 0;
                }
                return round((($this->regular_price - $this->sale_price) / $this->regular_price) * 100);
            }
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
    */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }
    // Trong Class WpProduct
    public function getImageUrlAttribute()
    {
        // 1. Nếu là URL (bắt đầu bằng http)
        if (str_starts_with($this->image, 'http')) {
            return $this->image;
        }

        // 2. Nếu là đường dẫn local (products/xyz.png)
        if ($this->image && Storage::disk('public')->exists($this->image)) {
            return asset('storage/' . $this->image);
        }

        // 3. Ảnh mặc định nếu không tìm thấy
        return asset('images/placeholder.jpg');
    }

    public function getGalleryUrlsAttribute()
    {
        // Ép kiểu mảng nếu dữ liệu đang là string JSON
        $gallery = is_array($this->gallery) ? $this->gallery : json_decode($this->gallery, true);

        if (!$gallery) return [];

        return collect($gallery)->map(function ($path) {
            if (str_starts_with($path, 'http')) return $path;
            return asset('storage/' . $path);
        })->toArray();
    }

    public function reviews()
    {
        // Lấy các đánh giá đã được duyệt, sắp xếp mới nhất
        return $this->hasMany(Review::class, 'product_id')
                    ->where('is_approved', true)
                    ->latest();
    }

    // Helper tính điểm trung bình sao (VD: 4.5)
    public function getAverageRatingAttribute()
    {
        return round($this->reviews()->avg('rating'), 1) ?? 0;
    }

    // Helper đếm tổng số đánh giá
    public function getReviewCountAttribute()
    {
        return $this->reviews()->count();
    }
    public function wishlists(): HasMany
    {
        return $this->hasMany(Wishlist::class, 'product_id');
    }
}
