<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Post\Models\Post;
use Modules\System\Services\SettingsService;

class PharmaBlogSection extends Component
{
    public string $sectionKey = 'blog';
    public int $visibleCount = 4;

    public function mount(string $sectionKey): void
    {
        $this->sectionKey = $sectionKey;
        $this->visibleCount = $this->config()['limit'];
    }

    private function config(): array
    {
        $sections = app(SettingsService::class)->get('website.pharma_homepage.blog_sections', []);
        $sections = is_array($sections) ? $sections : [];
        $defaults = ['eyebrow' => 'Góc kiến thức', 'title' => 'Tin tức & Kiến thức dược', 'description' => '', 'category_ids' => [], 'sort' => 'newest', 'period' => 'all', 'limit' => 4, 'display_mode' => 'load_more', 'card_style' => 'grid'];
        $section = ($this->sectionKey === 'blog' || preg_match('/^blog_[a-f0-9]{32}$/', $this->sectionKey)) ? ($sections[$this->sectionKey] ?? []) : [];
        $config = array_merge($defaults, is_array($section) ? $section : []);
        $config['limit'] = in_array((int) $config['limit'], [4, 8, 12, 16], true) ? (int) $config['limit'] : 4;
        $config['display_mode'] = in_array($config['display_mode'], ['fixed', 'load_more'], true) ? $config['display_mode'] : 'load_more';
        $config['card_style'] = in_array($config['card_style'], ['grid', 'list'], true) ? $config['card_style'] : 'grid';
        return $config;
    }

    public function loadMore(): void
    {
        if ($this->config()['display_mode'] === 'load_more') {
            $this->visibleCount = min(160, $this->visibleCount + $this->config()['limit']);
        }
    }

    public function render()
    {
        $config = $this->config();
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $config['category_ids']))));
        $query = Post::query()->where('status', 'published')->whereNotNull('published_at')->where('published_at', '<=', now())
            ->with(['categories' => fn ($q) => $q->where('type', 'post')]);
        if ($ids) {
            $query->whereHas('categories', fn ($q) => $q->where('type', 'post')->whereIn('categories.id', $ids));
        }
        if (in_array((string) $config['period'], ['7', '30', '90'], true)) {
            $query->where('published_at', '>=', now()->subDays((int) $config['period']));
        }
        switch ($config['sort']) {
            case 'oldest': $query->orderBy('published_at'); break;
            case 'name_asc': $query->orderBy('name'); break;
            case 'name_desc': $query->orderByDesc('name'); break;
            default: $query->orderByDesc('published_at');
        }
        $query->orderBy('id');
        $take = $config['display_mode'] === 'load_more' ? min(160, max($config['limit'], $this->visibleCount)) : $config['limit'];
        $items = $query->limit($take + 1)->get();
        $hasMore = $items->count() > $take;
        $posts = $items->take($take);
        $theme = app(SettingsService::class)->get('website.pharma_homepage.theme', []);
        $primary = is_array($theme) ? ($theme['primary'] ?? '#047857') : '#047857';
        $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $primary) ? $primary : '#047857';
        return view('Website::livewire.home.pharma-blog-section', compact('config', 'posts', 'hasMore', 'primary'));
    }
}
