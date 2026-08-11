<?php

namespace Modules\Website\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Category\Models\Category;
use Modules\Post\Models\Post;
use Modules\Product\Models\Product;
use Modules\System\Services\SettingsService;
use Modules\Website\Models\Banner;
use Modules\Website\Models\FlashSale;
use Modules\Website\Models\WebsitePage;
use Modules\Website\Models\WebsiteSection;

class HomepageContentService
{
    public const CACHE_KEY = 'website.homepage.composition';

    public function __construct(private readonly SettingsService $settings) {}

    public function visibility(): array
    {
        $page = $this->page();

        if (! $page) {
            return collect($this->sectionKeys())->mapWithKeys(fn (string $key): array => [
                'show_'.$key => $this->settings->get('home_show_'.$key, 'all'),
            ])->all();
        }

        return $page->sections->mapWithKeys(fn (WebsiteSection $section): array => [
            'show_'.$section->key => $section->is_enabled
                ? ($section->config['visibility'] ?? 'all')
                : 'hidden',
        ])->all();
    }

    public function order(): array
    {
        $page = $this->page();

        if (! $page) {
            return $this->sectionKeys();
        }

        return $page->sections->pluck('key')->all();
    }

    public function sectionTypes(): array
    {
        $page = $this->page();

        return $page
            ? $page->sections->mapWithKeys(fn (WebsiteSection $section): array => [$section->key => $section->type])->all()
            : collect($this->sectionKeys())->mapWithKeys(fn (string $key): array => [$key => $key])->all();
    }

    public function referenceIds(string $sectionKey, string $referenceType, string $legacyKey): array
    {
        $section = $this->section($sectionKey);

        if (! $section) {
            return $this->ids($this->settings->get($legacyKey, []));
        }

        return $section->items
            ->where('is_enabled', true)
            ->where('reference_type', $referenceType)
            ->pluck('reference_id')->map(fn ($id): int => (int) $id)->all();
    }

    public function limit(string $sectionKey, string $legacyKey, int $default): int
    {
        $section = $this->section($sectionKey);

        return $section
            ? (int) ($section->config['limit'] ?? $default)
            : (int) $this->settings->get($legacyKey, $default);
    }

    public function config(string $sectionKey, string $legacyKey): array
    {
        $section = $this->section($sectionKey);

        if (! $section) {
            return (array) $this->settings->get($legacyKey, []);
        }

        return collect($section->config ?? [])->except(['visibility', 'legacy_source'])->all();
    }

    public function itemConfigs(string $sectionKey, string $legacyKey): array
    {
        $section = $this->section($sectionKey);

        if (! $section) {
            return (array) $this->settings->get($legacyKey, []);
        }

        return $section->items->where('is_enabled', true)->pluck('config')->filter()->values()->all();
    }

    public function highlightedCategories(array $ids, int $fallbackLimit = 8): Collection
    {
        $query = Category::query()->where('is_active', true);

        if ($ids === []) {
            return $query->limit($fallbackLimit)->get();
        }

        return $this->preserveIdOrder($query->whereIn('id', $ids), $ids)->get();
    }

    public function featuredProducts(): Collection
    {
        $ids = $this->referenceIds('featured', 'product', 'home_featured_ids');
        $query = Product::query()->where('is_active', true)->with('categories')->withAvg('reviews', 'rating')->withCount('reviews');

        return $ids === []
            ? $query->latest()->limit(10)->get()
            : $this->preserveIdOrder($query->whereIn('id', $ids), $ids)->get();
    }

    public function newArrivals(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->latest('created_at')
            ->limit($this->limit('new_arrivals', 'home_new_arrivals_count', 10))
            ->with('categories')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();
    }

    public function bestSellers(): Collection
    {
        return Product::query()
            ->where('is_active', true)
            ->orderByDesc('sold_count')
            ->limit($this->limit('best_sellers', 'home_best_sellers_count', 8))
            ->with('categories')
            ->withAvg('reviews', 'rating')
            ->withCount('reviews')
            ->get();
    }

    public function latestPosts(): Collection
    {
        return Post::query()
            ->where('status', 'published')
            ->whereNotNull('published_at')
            ->whereDoesntHave('categories', fn ($query) => $query->where('slug', 'pages'))
            ->with(['categories:id,name,slug', 'author:id,name'])
            ->orderByDesc('published_at')
            ->limit($this->limit('blog_highlight', 'home_blog_count', 3))
            ->get();
    }

    public function heroSlides(): array
    {
        return Banner::query()
            ->where('position', 'hero')
            ->active()
            ->orderBy('order')
            ->orderByDesc('id')
            ->get()
            ->toArray();
    }

    public function activeFlashSale(): ?FlashSale
    {
        return FlashSale::query()
            ->where('is_active', true)
            ->where('start_time', '<=', now())
            ->where('end_time', '>=', now())
            ->with(['items.product:id,title,slug,image,regular_price,sale_price'])
            ->first();
    }

    private function page(): ?WebsitePage
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(15), fn () => WebsitePage::query()
            ->published()
            ->where('slug', 'home')
            ->with(['sections' => fn ($query) => $query->with('items')])
            ->first());
    }

    public static function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function section(string $key): ?WebsiteSection
    {
        return $this->page()?->sections->firstWhere('key', $key);
    }

    private function ids(mixed $value): array
    {
        return collect((array) $value)->map(fn ($id): int => (int) $id)->filter()->unique()->values()->all();
    }

    private function sectionKeys(): array
    {
        return [
            'hero', 'categories', 'flash_sale', 'featured', 'new_arrivals',
            'best_sellers', 'blog_highlight', 'promo_banner', 'trust_badges', 'newsletter',
        ];
    }

    private function preserveIdOrder($query, array $ids)
    {
        $cases = collect(array_values($ids))
            ->map(fn ($id, $position): string => 'WHEN '.(int) $id.' THEN '.$position)
            ->implode(' ');

        return $query->orderByRaw("CASE id {$cases} ELSE ".count($ids).' END');
    }
}
