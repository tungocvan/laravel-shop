<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Modules\Website\Models\WebsitePage;

class HomepageBackfillService
{
    private const SECTIONS = [
        'hero', 'categories', 'flash_sale', 'featured', 'new_arrivals',
        'best_sellers', 'blog_highlight', 'promo_banner', 'trust_badges', 'newsletter',
    ];

    public function backfill(bool $apply = false, ?array $sectionOrder = null): array
    {
        $this->assertSchema();
        $settings = DB::table('wp_settings')
            ->where('key', 'like', 'home%')
            ->get(['key', 'value', 'type'])
            ->mapWithKeys(fn (object $row): array => [$row->key => $this->decode($row)])
            ->all();

        $categoryIds = $this->ids($settings['home_category_ids'] ?? []);
        $productIds = $this->ids($settings['home_featured_ids'] ?? []);
        $existingCategories = Category::query()->whereIn('id', $categoryIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $existingProducts = Product::query()->whereIn('id', $productIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $orderedKeys = $this->orderedKeys($sectionOrder);
        $report = [
            'apply' => $apply,
            'sections' => count($orderedKeys),
            'category_items' => count($existingCategories),
            'product_items' => count($existingProducts),
            'trust_badge_items' => count((array) ($settings['home_trust_badges'] ?? [])),
            'missing_category_ids' => array_values(array_diff($categoryIds, $existingCategories)),
            'missing_product_ids' => array_values(array_diff($productIds, $existingProducts)),
        ];

        if (! $apply) {
            return $report;
        }

        DB::transaction(function () use ($settings, $existingCategories, $existingProducts, $orderedKeys): void {
            $page = WebsitePage::query()->updateOrCreate(
                ['slug' => 'home'],
                ['title' => 'Trang chủ', 'status' => WebsitePage::STATUS_PUBLISHED, 'template' => 'homepage']
            );

            foreach ($orderedKeys as $index => $key) {
                $existingSection = $page->sections()->where('key', $key)->first();
                $visibility = (string) ($settings['home_show_'.$key] ?? 'all');

                if (! in_array($key, self::SECTIONS, true) && $existingSection) {
                    $visibility = (string) ($settings['home_show_'.$key] ?? ($existingSection->config['visibility'] ?? 'all'));
                    $existingSection->update([
                        'position' => ($index + 1) * 10,
                        'is_enabled' => ! in_array($visibility, ['hidden', 'none'], true),
                        'config' => array_merge($existingSection->config ?? [], ['visibility' => $visibility]),
                    ]);

                    continue;
                }

                $section = $page->sections()->updateOrCreate(
                    ['key' => $key],
                    [
                        'type' => $this->sectionType($key),
                        'position' => ($index + 1) * 10,
                        'is_enabled' => $visibility !== 'hidden',
                        'config' => $this->sectionConfig($key, $settings, $visibility),
                    ]
                );

                $items = match ($key) {
                    'categories' => collect($existingCategories)->map(fn (int $id, int $position): array => ['reference_type' => 'category', 'reference_id' => $id, 'position' => $position * 10]),
                    'featured' => collect($existingProducts)->map(fn (int $id, int $position): array => ['reference_type' => 'product', 'reference_id' => $id, 'position' => $position * 10]),
                    'trust_badges' => collect((array) ($settings['home_trust_badges'] ?? []))->values()->map(fn (array $item, int $position): array => ['reference_type' => null, 'reference_id' => null, 'position' => $position * 10, 'config' => $item]),
                    default => collect(),
                };

                $section->items()->delete();
                $section->items()->createMany($items->all());
            }
        });

        HomepageContentService::clearCache();

        return $report;
    }

    private function sectionType(string $key): string
    {
        return match ($key) {
            'categories' => 'category_grid',
            'featured', 'new_arrivals', 'best_sellers' => 'product_grid',
            'blog_highlight' => 'post_grid',
            default => $key,
        };
    }

    private function sectionConfig(string $key, array $settings, string $visibility): array
    {
        $config = ['visibility' => $visibility, 'legacy_source' => 'wp_settings'];

        return $config + match ($key) {
            'new_arrivals' => ['limit' => (int) ($settings['home_new_arrivals_count'] ?? 10)],
            'best_sellers' => ['limit' => (int) ($settings['home_best_sellers_count'] ?? 8)],
            'blog_highlight' => ['limit' => (int) ($settings['home_blog_count'] ?? 3)],
            'promo_banner' => (array) ($settings['home_promo_banner'] ?? []),
            'newsletter' => (array) ($settings['home_newsletter'] ?? []),
            default => [],
        };
    }

    private function decode(object $row): mixed
    {
        return $row->type === 'json'
            ? json_decode((string) $row->value, true, flags: JSON_THROW_ON_ERROR)
            : $row->value;
    }

    private function ids(mixed $value): array
    {
        return collect((array) $value)->map(fn ($id) => (int) $id)->filter()->unique()->values()->all();
    }

    private function assertSchema(): void
    {
        foreach (['wp_settings', 'website_pages', 'website_sections', 'website_section_items'] as $table) {
            if (! Schema::hasTable($table)) {
                throw new \RuntimeException("Thiếu bảng bắt buộc: {$table}");
            }
        }
    }

    private function orderedKeys(?array $requested): array
    {
        if ($requested === null) {
            $existing = WebsitePage::query()->where('slug', 'home')->first()?->sections()->pluck('key')->all() ?? [];
            $requested = $existing ?: self::SECTIONS;
        }

        $valid = collect($requested)
            ->map(fn ($key): string => str_starts_with((string) $key, 'show_') ? substr((string) $key, 5) : (string) $key)
            ->filter(fn (string $key): bool => in_array($key, self::SECTIONS, true)
                || WebsitePage::query()->where('slug', 'home')->whereHas('sections', fn ($query) => $query->where('key', $key))->exists())
            ->unique()->values();

        return $valid->merge(array_diff(self::SECTIONS, $valid->all()))->values()->all();
    }
}
