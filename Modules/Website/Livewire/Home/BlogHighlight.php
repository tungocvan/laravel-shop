<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Admin\Models\Setting;
use Modules\Website\Models\Post; // Import đúng Model của bạn
use Illuminate\Database\Eloquent\Collection;

class BlogHighlight extends Component
{
    /**
     * UI Skeleton: Hiển thị khi đang tải
     */
    public function placeholder()
    {
        return <<<'blade'
        <div class="container mx-auto px-4 mb-20">
            <div class="text-center mb-10">
                <div class="h-8 bg-gray-200 rounded w-48 mx-auto mb-2"></div>
                <div class="h-4 bg-gray-200 rounded w-64 mx-auto"></div>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                @foreach(range(1, 3) as $i)
                    <div class="animate-pulse">
                        <div class="bg-gray-200 rounded-xl aspect-video mb-4"></div>
                        <div class="h-4 bg-gray-200 rounded w-full mb-2"></div>
                        <div class="h-4 bg-gray-200 rounded w-2/3"></div>
                    </div>
                @endforeach
            </div>
        </div>
        blade;
    }

    public function render()
    {
        // 1. Lấy cấu hình số lượng từ Admin (Mặc định 3)
        $limit = Setting::where('key', 'home_blog_count')->value('value');
        $limit = $limit ? (int)$limit : 3;

        // 2. Query bài viết theo Model Post của bạn
        $posts = Post::where('status', 'published') // Chỉ lấy bài đã xuất bản
            ->whereNotNull('published_at')          // Phải có ngày xuất bản
            ->whereDoesntHave('categories', function ($query) {
                $query->where('slug', 'pages');
                // Lưu ý: Kiểm tra lại trong DB xem slug là 'pages' hay 'pages' nhé
            })
            ->orderBy('published_at', 'desc')       // Mới nhất lên đầu
            ->take($limit)
            ->get();

        return view('Website::livewire.home.blog-highlight', [
            'posts' => $posts
        ]);
    }
}
