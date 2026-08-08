<?php

namespace Modules\Website\Livewire\Post;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Website\Models\Post;
use Modules\Website\Models\Category;

class PostList extends Component
{
    use WithPagination;

    public $categorySlug = null;
    public $currentCategory = null;

    // Slug của danh mục Trang Tĩnh cần loại bỏ
    const STATIC_PAGE_SLUG = 'pages';

    public function mount($categorySlug = null)
    {
        $this->categorySlug = $categorySlug;

        if ($categorySlug) {
            $this->currentCategory = Category::where('slug', $categorySlug)->first();
        }
    }

    public function render()
    {
        // 1. Lấy danh sách danh mục cho Sidebar (Trừ Trang Tĩnh)
        $categories = Category::where('type', 'post')
            ->where('slug', '!=', self::STATIC_PAGE_SLUG)
            ->where('is_active', true)
            ->withCount(['posts' => function ($q) {
                $q->where('status', 'published');
            }])
            ->get();

        // 2. Query Bài viết
        $postsQuery = Post::where('status', 'published')
            ->with(['categories', 'user'])
            ->latest('published_at');

        if ($this->categorySlug) {
            // Trường hợp 1: Đang lọc theo danh mục cụ thể
            $postsQuery->whereHas('categories', function ($q) {
                $q->where('slug', $this->categorySlug);
            });
        } else {
            // Trường hợp 2: Xem tất cả -> Phải loại bỏ bài viết thuộc Trang Tĩnh
            $postsQuery->whereDoesntHave('categories', function ($q) {
                $q->where('slug', self::STATIC_PAGE_SLUG);
            });
        }

        // Master UI: Lấy bài Hero (Bài mới nhất) nếu đang ở trang 1 và không lọc danh mục
        $heroPost = null;
        $currentPage = $this->getPage();

        if ($currentPage == 1 && !$this->categorySlug) {
            $heroPost = $postsQuery->first();
            // Clone query để phân trang các bài còn lại (bỏ bài Hero ra)
            $listQuery = $postsQuery->clone();

            if ($heroPost) {
                $posts = $listQuery->skip(1)->paginate(9);
            } else {
                $posts = $listQuery->paginate(9);
            }
        } else {
            $posts = $postsQuery->paginate(9);
        }

        return view('Website::livewire.post.post-list', [
            'categories' => $categories,
            'posts' => $posts,
            'heroPost' => $heroPost
        ]);
    }
}
