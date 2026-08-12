<?php

namespace Modules\Website\Livewire\Post;

use Livewire\Component;
use Livewire\WithPagination;
use Modules\Post\Services\PostService;

class PostList extends Component
{
    use WithPagination;

    public $categorySlug = null;

    public $currentCategory = null;

    // Slug của danh mục Trang Tĩnh cần loại bỏ
    const STATIC_PAGE_SLUG = 'pages';

    public function mount(PostService $posts, $categorySlug = null)
    {
        $this->categorySlug = $categorySlug;

        if ($categorySlug) {
            $this->currentCategory = $posts->findCategoryBySlug($categorySlug);
        }
    }

    public function render(PostService $service)
    {
        // 1. Lấy danh sách danh mục cho Sidebar (Trừ Trang Tĩnh)
        $categories = $service->storefrontCategories(self::STATIC_PAGE_SLUG);
        $result = $service->paginateStorefront($this->categorySlug, $this->getPage());

        return view('Website::livewire.post.post-list', [
            'categories' => $categories,
            'posts' => $result['posts'],
            'heroPost' => $result['hero'],
        ]);
    }
}
