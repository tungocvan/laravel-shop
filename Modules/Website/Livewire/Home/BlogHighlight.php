<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Website\Services\HomepageContentService;

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

    public function render(HomepageContentService $homepage)
    {
        $posts = $homepage->latestPosts();

        return view('Website::livewire.home.blog-highlight', [
            'posts' => $posts,
        ]);
    }
}
