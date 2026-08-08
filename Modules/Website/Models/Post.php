<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\User;

class Post extends Model
{
    use SoftDeletes;

    protected $table = 'wp_posts';

    protected $fillable = [
        'name', 'slug', 'summary', 'content', 'thumbnail',
        'is_featured', 'status', 'views', 'user_id', 'published_at',
        'meta_title', 'meta_description'
    ];

    protected $casts = [
        'is_featured' => 'boolean',
        'published_at' => 'datetime',
    ];

    // Quan hệ với Danh mục (Nhiều nhiều)
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_post', 'post_id', 'category_id');
    }
    public function user()
    {
        // Bài viết thuộc về 1 User (thông qua cột user_id)
        return $this->belongsTo(User::class, 'user_id');
    }
    // Quan hệ với Tags (Nhiều nhiều)
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'wp_post_tag', 'post_id', 'tag_id');
    }

    // Quan hệ với Tác giả (User)
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Helper: Trạng thái hiển thị (Badge)
    public function getStatusBadgeAttribute()
    {
        if ($this->status === 'published') {
            return '<span class="px-2 py-1 text-xs font-medium text-green-700 bg-green-100 rounded-md">Đã xuất bản</span>';
        } elseif ($this->status === 'draft') {
            return '<span class="px-2 py-1 text-xs font-medium text-gray-700 bg-gray-100 rounded-md">Bản nháp</span>';
        } else {
            return '<span class="px-2 py-1 text-xs font-medium text-red-700 bg-red-100 rounded-md">Đã ẩn</span>';
        }
    }
}
