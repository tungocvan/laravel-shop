<?php

namespace Modules\Website\Livewire\Post;

use Livewire\Component;
use Modules\Website\Models\Post;

class PostDetail extends Component
{
    public $post;
    public $relatedPosts;
    public $readingTime;

    public function mount($slug)
    {
        // Query bài viết
        $this->post = Post::where('slug', $slug)
            ->where('status', 'published')
            ->with(['categories', 'user', 'tags'])
            ->firstOrFail();

        // Tăng view (đơn giản)
        $this->post->increment('views');

        // Tính thời gian đọc
        $wordCount = str_word_count(strip_tags($this->post->content));
        $this->readingTime = ceil($wordCount / 200);

        // Lấy bài liên quan
        $this->relatedPosts = collect();
        if ($this->post->categories->isNotEmpty()) {
            $catIds = $this->post->categories->pluck('id');
            $this->relatedPosts = Post::where('status', 'published')
                ->where('id', '!=', $this->post->id)
                ->whereHas('categories', fn($q) => $q->whereIn('id', $catIds))
                ->latest('published_at')
                ->take(3)
                ->get();
        }
    }

    public function render()
    {
        return view('Website::livewire.post.post-detail');
    }
}
