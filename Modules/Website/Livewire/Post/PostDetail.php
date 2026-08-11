<?php

namespace Modules\Website\Livewire\Post;

use Livewire\Component;
use Modules\Post\Services\PostService;

class PostDetail extends Component
{
    public $post;

    public $relatedPosts;

    public $readingTime;

    public function mount($slug, PostService $posts)
    {
        // Query bài viết
        $this->post = $posts->findPublishedBySlug($slug);

        // Tăng view (đơn giản)
        $this->post->increment('views');

        // Tính thời gian đọc
        $wordCount = str_word_count(strip_tags($this->post->content));
        $this->readingTime = ceil($wordCount / 200);

        // Lấy bài liên quan
        $this->relatedPosts = $posts->relatedPublished($this->post);
    }

    public function render()
    {
        return view('Website::livewire.post.post-detail');
    }
}
