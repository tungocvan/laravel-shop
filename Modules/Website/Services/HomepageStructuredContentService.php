<?php

namespace Modules\Website\Services;

use Modules\Website\Models\WebsitePage;
use Modules\Website\Models\WebsiteSection;

class HomepageStructuredContentService
{
    public function sync(array $values): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->firstOrFail();
        $sections = $page->sections()->with('items')->get()->keyBy('key');

        $this->syncReferences($sections->get('categories'), 'category', (array) ($values['home_category_ids'] ?? []));
        $this->syncReferences($sections->get('featured'), 'product', (array) ($values['home_featured_ids'] ?? []));
        $this->syncItemConfigs($sections->get('trust_badges'), (array) ($values['home_trust_badges'] ?? []));

        $this->mergeConfig($sections->get('new_arrivals'), [
            'limit' => (int) ($values['home_new_arrivals_count'] ?? 10),
        ]);
        $this->mergeConfig($sections->get('best_sellers'), [
            'limit' => (int) ($values['home_best_sellers_count'] ?? 8),
        ]);
        $this->mergeConfig($sections->get('blog_highlight'), [
            'limit' => (int) ($values['home_blog_count'] ?? 3),
        ]);
        $this->mergeConfig($sections->get('promo_banner'), (array) ($values['home_promo_banner'] ?? []));
        $this->mergeConfig($sections->get('newsletter'), (array) ($values['home_newsletter'] ?? []));

        HomepageContentService::clearCache();
    }

    private function syncReferences(?WebsiteSection $section, string $type, array $ids): void
    {
        if (! $section) {
            return;
        }

        $ids = collect($ids)->map(fn ($id): int => (int) $id)->filter()->unique()->values();
        $section->items()->delete();
        $section->items()->createMany($ids->map(fn (int $id, int $index): array => [
            'reference_type' => $type,
            'reference_id' => $id,
            'position' => ($index + 1) * 10,
            'is_enabled' => true,
            'config' => [],
        ])->all());
    }

    private function syncItemConfigs(?WebsiteSection $section, array $items): void
    {
        if (! $section) {
            return;
        }

        $clean = collect($items)
            ->filter(fn ($item): bool => is_array($item) && filled($item['title'] ?? null))
            ->values();

        $section->items()->delete();
        $section->items()->createMany($clean->map(fn (array $item, int $index): array => [
            'reference_type' => null,
            'reference_id' => null,
            'position' => ($index + 1) * 10,
            'is_enabled' => true,
            'config' => [
                'icon' => (string) ($item['icon'] ?? ''),
                'title' => (string) ($item['title'] ?? ''),
                'sub_title' => (string) ($item['sub_title'] ?? ''),
            ],
        ])->all());
    }

    private function mergeConfig(?WebsiteSection $section, array $config): void
    {
        if (! $section) {
            return;
        }

        $section->update([
            'config' => array_merge(
                $section->config ?? [],
                $config,
                ['content_source' => 'structured']
            ),
        ]);
    }
}
