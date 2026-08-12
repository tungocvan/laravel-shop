<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\DB;
use Modules\System\Services\SettingsService;
use Modules\Website\Models\WebsitePage;
use Modules\Website\Models\WebsiteSection;

class HomepageSectionManagerService
{
    public function __construct(private readonly SettingsService $settings) {}

    public function duplicate(string $key): WebsiteSection
    {
        $copy = DB::transaction(function () use ($key): WebsiteSection {
            $source = $this->section($key);
            $copy = $source->replicate();
            $copy->key = $this->copyKey($source->key);
            $copy->position = $source->position + 1;
            $copy->is_enabled = true;
            $copy->config = array_merge($source->config ?? [], ['visibility' => 'all', 'duplicated_from' => $source->key]);
            $copy->save();

            foreach ($source->items as $item) {
                $copy->items()->create($item->only(['reference_type', 'reference_id', 'position', 'is_enabled', 'config']));
            }

            return $copy->load('items');
        });

        HomepageContentService::clearCache();

        return $copy;
    }

    public function remove(string $key): void
    {
        $section = $this->section($key);

        if ($this->isCanonical($key)) {
            $config = array_merge($section->config ?? [], ['visibility' => 'hidden']);
            $section->update(['is_enabled' => false, 'config' => $config]);
            $this->settings->set('home_show_'.$key, 'hidden', 'homepage');
            HomepageContentService::clearCache();

            return;
        }

        $section->delete();
        HomepageContentService::clearCache();
    }

    public function restore(string $key): void
    {
        $section = $this->section($key);
        $config = array_merge($section->config ?? [], ['visibility' => 'all']);
        $section->update(['is_enabled' => true, 'config' => $config]);
        $this->settings->set('home_show_'.$key, 'all', 'homepage');
        HomepageContentService::clearCache();
    }

    private function section(string $key): WebsiteSection
    {
        return WebsitePage::query()->where('slug', 'home')->firstOrFail()
            ->sections()->with('items')->where('key', $key)->firstOrFail();
    }

    private function copyKey(string $key): string
    {
        $base = preg_replace('/_copy_\d+$/', '', $key);
        $number = 1;
        do {
            $candidate = $base.'_copy_'.$number++;
        } while (WebsiteSection::query()->where('key', $candidate)->exists());

        return $candidate;
    }

    private function isCanonical(string $key): bool
    {
        return in_array($key, [
            'hero', 'categories', 'flash_sale', 'featured', 'new_arrivals',
            'best_sellers', 'blog_highlight', 'promo_banner', 'trust_badges', 'newsletter',
        ], true);
    }
}
