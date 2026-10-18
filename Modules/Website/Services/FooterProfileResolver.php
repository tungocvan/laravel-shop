<?php

namespace Modules\Website\Services;

use Illuminate\Support\Collection;
use Modules\System\Services\SettingsService;

class FooterProfileResolver
{
    public function __construct(private readonly SettingsService $settings)
    {
    }

    public function resolve(string $prefix, array $footerSettings, Collection $columns, Collection $socialLinks): array
    {
        $active = (string) $this->settings->get($prefix.'active_theme', '');
        if ($active === '') {
            return ['settings' => $footerSettings, 'columns' => $columns, 'social_links' => $socialLinks];
        }

        $profiles = $this->settings->get($prefix.'layout_themes', []);
        $published = $this->settings->get($prefix.'published_profile');
        $profile = is_array($published) ? $published : (is_array($profiles) ? ($profiles[$active] ?? null) : null);
        if (! is_array($profile)) {
            return ['settings' => $footerSettings, 'columns' => $columns, 'social_links' => $socialLinks];
        }

        $content = is_array($profile['content'] ?? null) ? $profile['content'] : [];
        foreach (['brand' => ['brand_name', 'logo', 'description'],
                  'contact' => ['address', 'email', 'phone'],
                  'legal' => ['copyright', 'legal_links', 'trust_badges'],
                  'app_install' => ['app_title', 'app_description', 'app_button_title', 'app_button_subtitle', 'appstore', 'playstore']] as $group => $allowed) {
            $definition = $content[$group] ?? [];
            if (! is_array($definition) || ($definition['source'] ?? 'global') !== 'profile') {
                continue;
            }
            $overrides = is_array($definition['overrides'] ?? null) ? $definition['overrides'] : [];
            $footerSettings = array_replace($footerSettings, array_intersect_key($overrides, array_flip($allowed)));
        }

        $columnDefinition = $profile['columns'] ?? [];
        if (is_array($columnDefinition) && ($columnDefinition['source'] ?? 'global') === 'profile') {
            $items = is_array($columnDefinition['items'] ?? null) ? $columnDefinition['items'] : [];
            $columns = collect(array_slice($items, 0, 6))->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true))
                ->map(function ($item) {
                    $links = collect(array_slice(is_array($item['items'] ?? null) ? $item['items'] : [], 0, 12))
                        ->filter(fn ($link) => is_array($link) && ($link['type'] ?? '') === 'link' && filled($link['label'] ?? '') && filled($link['url'] ?? ''))
                        ->map(fn ($link) => (object) [
                            'label' => (string) $link['label'],
                            'url' => (string) $link['url'],
                            'new_tab' => (bool) ($link['new_tab'] ?? false),
                        ]);
                    return (object) ['title' => (string) ($item['title'] ?? ''), 'links' => $links];
                })->values();
        }

        $socialDefinition = $profile['social_links'] ?? [];
        if (is_array($socialDefinition) && ($socialDefinition['source'] ?? 'global') === 'profile') {
            $items = is_array($socialDefinition['items'] ?? null) ? $socialDefinition['items'] : [];
            $socialLinks = collect(array_slice($items, 0, 20))
                ->filter(fn ($item) => is_array($item) && ($item['enabled'] ?? true) && filled($item['url'] ?? ''))
                ->map(fn ($item) => (object) [
                    'name' => (string) ($item['name'] ?? ''),
                    'platform' => (string) ($item['platform'] ?? ''),
                    'url' => (string) $item['url'],
                ])->values();
        }

        return ['settings' => $footerSettings, 'columns' => $columns, 'social_links' => $socialLinks];
    }
}
