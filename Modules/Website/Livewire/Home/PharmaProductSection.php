<?php

namespace Modules\Website\Livewire\Home;

use Livewire\Component;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;

class PharmaProductSection extends Component
{
    public string $sectionKey = 'new_arrivals';
    public int $visibleCount = 8;
    public int $page = 1;

    public function mount(string $sectionKey): void
    {
        $this->sectionKey = $sectionKey;
        $this->visibleCount = $this->config()['limit'];
    }

    private function config(): array
    {
        $sections = app(SettingsService::class)->get('website.pharma_homepage.product_sections', []);
        $sections = is_array($sections) ? $sections : [];
        $defaults = ['eyebrow' => 'Danh mục sản phẩm', 'title' => 'Sản phẩm mới cập nhật', 'category_ids' => [], 'sort' => 'newest', 'period' => 'all', 'limit' => 8, 'display_mode' => 'load_more'];
        $section = $this->sectionKey === 'new_arrivals' || preg_match('/^products_[a-f0-9]{32}$/', $this->sectionKey)
            ? ($sections[$this->sectionKey] ?? [])
            : [];
        $config = array_merge($defaults, is_array($section) ? $section : []);
        $config['limit'] = in_array((int) $config['limit'], [4, 8, 12, 16, 20, 24], true) ? (int) $config['limit'] : 8;
        $config['display_mode'] = in_array($config['display_mode'], ['load_more', 'fixed', 'pagination'], true) ? $config['display_mode'] : 'load_more';

        return $config;
    }

    public function loadMore(): void
    {
        if ($this->config()['display_mode'] === 'load_more') {
            $this->visibleCount = min(240, $this->visibleCount + $this->config()['limit']);
        }
    }

    public function nextPage(): void
    {
        if ($this->config()['display_mode'] === 'pagination') {
            $this->page = min(1000, $this->page + 1);
        }
    }

    public function previousPage(): void
    {
        $this->page = max(1, $this->page - 1);
    }

    public function render()
    {
        $config = $this->config();
        $ids = array_values(array_unique(array_filter(array_map('intval', (array) $config['category_ids']))));
        $query = Product::query()->active()->whereHas('pharmaLink')
            ->with(['categories' => fn ($q) => $q->where('type', 'medicine')]);
        if ($ids) {
            $query->whereHas('categories', fn ($q) => $q->where('type', 'medicine')->whereIn('categories.id', $ids));
        }
        if (in_array((string) $config['period'], ['7', '30', '90'], true)) {
            $query->where('updated_at', '>=', now()->subDays((int) $config['period']));
        }
        switch ($config['sort']) {
            case 'name_asc': $query->orderBy('title'); break;
            case 'name_desc': $query->orderByDesc('title'); break;
            case 'created': $query->orderByDesc('created_at'); break;
            default: $query->orderByDesc('updated_at');
        }
        $query->orderBy('id');
        $size = $config['limit'];
        $mode = $config['display_mode'];
        $take = $mode === 'load_more' ? min(240, max($size, $this->visibleCount)) : $size;
        $offset = $mode === 'pagination' ? (max(1, $this->page) - 1) * $size : 0;
        $items = (clone $query)->skip($offset)->take($take + 1)->get();
        $hasMore = $items->count() > $take;
        $products = $items->take($take);
        $theme = app(SettingsService::class)->get('website.pharma_homepage.theme', []);
        $primary = is_array($theme) ? ($theme['primary'] ?? '#047857') : '#047857';
        $primary = preg_match('/^#[0-9a-fA-F]{6}$/', $primary) ? $primary : '#047857';

        return view('Website::livewire.home.pharma-product-section', compact('config', 'products', 'hasMore', 'mode', 'primary'));
    }
}
