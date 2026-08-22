<?php

namespace Modules\Website\Services;

use Modules\Website\Models\WebsitePage;
use Modules\Website\Models\WebsiteSection;

class HomepageBuilderPersistenceService
{
    public function __construct(private readonly HomepageSectionRegistry $registry) {}

    public function sync(array $sectionOrder, array $layout, array $sectionTypes): void
    {
        $page = WebsitePage::query()->where('slug', 'home')->firstOrFail();
        $orderedKeys = collect($sectionOrder)
            ->map(fn ($key): string => $this->sectionKey((string) $key))
            ->filter()
            ->unique()
            ->values()
            ->all();

        $existing = $page->sections()->with('items')->get()->keyBy('key');

        foreach ($orderedKeys as $index => $key) {
            $definition = $this->registry->resolve($key, $sectionTypes[$key] ?? null);
            $visibility = $this->visibilityFor($layout, $key);
            $section = $existing->get($key);

            if (! $section) {
                $section = $this->createCopy($page, $key, $definition, $existing);
                $existing->put($key, $section);
            }

            $section->update([
                'type' => $definition['type'],
                'position' => ($index + 1) * 10,
                'is_enabled' => ! in_array($visibility, ['none', 'hidden'], true),
                'config' => array_merge($section->config ?? [], ['visibility' => $visibility]),
            ]);
        }

        $keep = collect($orderedKeys)->flip();
        $page->sections()
            ->where('key', 'regexp', '_copy_[0-9]+$')
            ->get()
            ->each(function (WebsiteSection $section) use ($keep): void {
                if (! $keep->has($section->key)) {
                    $section->delete();
                }
            });

        HomepageContentService::clearCache();
    }

    private function createCopy(WebsitePage $page, string $key, array $definition, $existing): WebsiteSection
    {
        $canonical = $this->registry->canonicalKey($key);
        if ($canonical === $key) {
            throw new \RuntimeException("Homepage canonical section [{$key}] is missing after backfill.");
        }

        $source = $existing->get($canonical)
            ?? $page->sections()->with('items')->where('key', $canonical)->firstOrFail();

        $copy = $source->replicate();
        $copy->key = $key;
        $copy->type = $definition['type'];
        $copy->is_enabled = true;
        $copy->config = array_merge($source->config ?? [], [
            'visibility' => 'all',
            'duplicated_from' => $canonical,
        ]);
        $copy->save();

        foreach ($source->items as $item) {
            $copy->items()->create($item->only([
                'reference_type',
                'reference_id',
                'position',
                'is_enabled',
                'config',
            ]));
        }

        return $copy->load('items');
    }

    private function sectionKey(string $layoutKey): string
    {
        return str_starts_with($layoutKey, 'show_') ? substr($layoutKey, 5) : $layoutKey;
    }

    private function visibilityFor(array $layout, string $sectionKey): string
    {
        $value = $layout['show_'.$sectionKey] ?? 'all';

        if ($value === true || $value === '1') {
            return 'all';
        }

        if ($value === false || $value === '0') {
            return 'hidden';
        }

        return in_array($value, ['all', 'desktop', 'mobile', 'none', 'hidden'], true)
            ? $value
            : 'all';
    }
}
