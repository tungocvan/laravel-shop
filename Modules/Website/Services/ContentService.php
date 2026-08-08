<?php

namespace Modules\Website\Services;

use Modules\Website\Models\Post;
use Illuminate\Database\Eloquent\Collection;

class ContentService
{
    /**
     * Lấy bài viết mới nhất cho trang chủ
     * Điều kiện: Status = published và ngày đăng <= hiện tại
     */
    public function getLatestPosts(int $limit = 3): Collection
    {
        return Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->where('published_at', '<=', now())
            ->orderBy('published_at', 'desc')
            ->take($limit)
            ->get();
    }
}
