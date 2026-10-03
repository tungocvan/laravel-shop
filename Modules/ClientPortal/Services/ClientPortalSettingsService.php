<?php

namespace Modules\ClientPortal\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\ClientPortal\Models\ClientPortalSetting;

class ClientPortalSettingsService
{
    private const CACHE_PREFIX = 'clientportal.settings.';

    public function pwaGeneral(): array
    {
        return $this->group('pwa.general', config('clientportal.pwa.general', []));
    }

    public function pwaBottomNavigation(): array
    {
        $settings = $this->group('pwa.bottom_navigation', [
            'background_color' => '#ffffff',
            'background_opacity' => 95,
            'icon_color' => '#64748b',
            'text_color' => '#64748b',
            'text_font_size' => 11,
            'icon_size' => 20,
            'min_height' => 72,
        ]);
        $settings['background_opacity'] = (int) ($settings['background_opacity'] ?? 95);
        $settings['text_font_size'] = (int) ($settings['text_font_size'] ?? 11);
        $settings['icon_size'] = (int) ($settings['icon_size'] ?? 20);
        $settings['min_height'] = (int) ($settings['min_height'] ?? 72);
        return $settings;
    }

    public function pwaLogin(): array
    {
        $defaults = config('clientportal.pwa.login', []);
        $settings = $this->group('pwa.login', $defaults);
        $settings['show_intro_panel'] = $this->bool($settings['show_intro_panel'] ?? true, true);
        $settings['feature_cards'] = collect($settings['feature_cards'] ?? [])->filter(fn ($card): bool => is_array($card))->map(fn (array $card): array => [
            'enabled' => $this->bool($card['enabled'] ?? true, true),
            'title' => trim((string) ($card['title'] ?? '')),
            'description' => trim((string) ($card['description'] ?? '')),
        ])->values()->all();
        return $settings;
    }

    public function pwaLauncher(): array
    {
        $settings = $this->group('pwa.launcher', config('clientportal.pwa.launcher', []));
        $settings['show_source_module'] = $this->bool($settings['show_source_module'] ?? true, true);
        return $settings;
    }

    public function applicationPresentation(array $application): array
    {
        $defaults = ['enabled' => true, 'name' => $application['name'], 'description' => $application['description'], 'sort_order' => $application['sort_order']];
        $settings = $this->group('application.'.$application['key'].'.presentation', $defaults);
        $settings['enabled'] = $this->bool($settings['enabled'] ?? true, true);
        $settings['sort_order'] = (int) ($settings['sort_order'] ?? $application['sort_order']);
        return $settings;
    }

    public function applicationHubPresentation(array $application): array
    {
        $hub = (array) ($application['hub'] ?? []);
        $supporting = (array) ($hub['supporting'] ?? []);
        $defaults = [
            'eyebrow' => $hub['eyebrow'] ?? $application['name'],
            'title' => $hub['title'] ?? $application['name'],
            'description' => $hub['description'] ?? $application['description'],
            'supporting_visible' => $supporting['visible'] ?? true,
            'supporting_title' => $supporting['title'] ?? '',
            'supporting_body' => $supporting['body'] ?? '',
        ];

        $legacyOverview = collect($application['features'] ?? [])->firstWhere('key', 'overview');
        $legacyGroup = 'application.'.$application['key'].'.feature.overview.presentation';
        if (is_array($legacyOverview) && $this->groupHasStoredValues($legacyGroup)) {
            $legacy = $this->featurePresentation($application['key'], $legacyOverview);
            $defaults['eyebrow'] = $legacy['eyebrow'];
            $defaults['title'] = $legacy['page_title'];
            $defaults['description'] = $legacy['page_description'];
        }

        $settings = $this->group('application.'.$application['key'].'.hub', $defaults);
        $settings['supporting_visible'] = $this->bool($settings['supporting_visible'] ?? true, true);

        return $settings;
    }

    public function applicationNavigationPresentation(array $application): array
    {
        $defaults = collect($application['navigation'] ?? [])->map(fn (array $item): array => [
            'key' => $item['key'],
            'bottom_enabled' => true,
            'bottom_sort_order' => (int) ($item['sort_order'] ?? 100),
            'bottom_icon' => $item['icon'] ?? 'squares-2x2',
        ])->values()->all();
        $settings = $this->group('application.'.$application['key'].'.navigation', ['items' => $defaults]);
        $stored = collect($settings['items'] ?? [])->filter(fn ($item): bool => is_array($item) && isset($item['key']))->keyBy('key');

        return ['items' => collect($defaults)->map(function (array $item) use ($stored): array {
            $override = (array) $stored->get($item['key'], []);
            return [
                'key' => $item['key'],
                'bottom_enabled' => $this->bool($override['bottom_enabled'] ?? $item['bottom_enabled'], true),
                'bottom_sort_order' => (int) ($override['bottom_sort_order'] ?? $item['bottom_sort_order']),
                'bottom_icon' => trim((string) ($override['bottom_icon'] ?? $item['bottom_icon'])),
            ];
        })->values()->all()];
    }

    public function featurePresentation(string $applicationKey, array $feature): array
    {
        $defaults = [
            'enabled' => true,
            'name' => $feature['name'],
            'description' => $feature['description'] ?? '',
            'sort_order' => $feature['sort_order'] ?? 100,
            'badge' => '',
            'eyebrow' => $feature['eyebrow'] ?? $feature['name'],
            'page_title' => $feature['page_title'] ?? $feature['name'],
            'page_description' => $feature['page_description'] ?? ($feature['description'] ?? ''),
            'maintenance' => false,
            'maintenance_message' => '',
        ];
        $settings = $this->group('application.'.$applicationKey.'.feature.'.$feature['key'].'.presentation', $defaults);
        $settings['enabled'] = $this->bool($settings['enabled'] ?? true, true);
        $settings['maintenance'] = $this->bool($settings['maintenance'] ?? false, false);
        $settings['sort_order'] = (int) ($settings['sort_order'] ?? ($feature['sort_order'] ?? 100));
        return $settings;
    }

    public function presentApplications(Collection $applications): Collection
    {
        return $applications->map(function (array $application): array {
            $presentation = $this->applicationPresentation($application);
            return array_replace($application, [
                'presentation_enabled' => $presentation['enabled'],
                'name' => trim((string) $presentation['name']),
                'description' => trim((string) $presentation['description']),
                'sort_order' => $presentation['sort_order'],
            ]);
        })->filter(fn (array $application): bool => $application['presentation_enabled'])->sortBy(fn (array $application): array => [$application['sort_order'], $application['name']])->values();
    }

    public function presentFeatures(string $applicationKey, Collection $features): Collection
    {
        return $features->map(function (array $feature) use ($applicationKey): array {
            $presentation = $this->featurePresentation($applicationKey, $feature);
            return array_replace($feature, [
                'presentation_enabled' => $presentation['enabled'],
                'name' => trim((string) $presentation['name']),
                'description' => trim((string) $presentation['description']),
                'sort_order' => $presentation['sort_order'],
                'badge' => trim((string) $presentation['badge']),
                'maintenance' => (bool) $presentation['maintenance'],
                'maintenance_message' => trim((string) $presentation['maintenance_message']),
            ]);
        })->filter(fn (array $feature): bool => $feature['presentation_enabled'])->sortBy(fn (array $feature): array => [$feature['sort_order'], $feature['name']])->values();
    }

    public function updatePwaGeneral(array $values, ?int $updatedBy = null): void { $this->updateGroup('pwa.general', $values, $updatedBy); }
    public function updatePwaBottomNavigation(array $values, ?int $updatedBy = null): void { $this->updateGroup('pwa.bottom_navigation', $values, $updatedBy); }
    public function updatePwaLogin(array $values, ?int $updatedBy = null): void { $this->updateGroup('pwa.login', $values, $updatedBy); }
    public function updatePwaLauncher(array $values, ?int $updatedBy = null): void { $this->updateGroup('pwa.launcher', $values, $updatedBy); }
    public function updateApplicationPresentation(string $applicationKey, array $values, ?int $updatedBy = null): void { $this->updateGroup('application.'.trim($applicationKey).'.presentation', $values, $updatedBy); }
    public function updateApplicationHubPresentation(string $applicationKey, array $values, ?int $updatedBy = null): void { $this->updateGroup('application.'.trim($applicationKey).'.hub', $values, $updatedBy); }
    public function updateApplicationNavigationPresentation(string $applicationKey, array $values, ?int $updatedBy = null): void { $this->updateGroup('application.'.trim($applicationKey).'.navigation', $values, $updatedBy); }
    public function updateFeaturePresentation(string $applicationKey, string $featureKey, array $values, ?int $updatedBy = null): void { $this->updateGroup('application.'.trim($applicationKey).'.feature.'.trim($featureKey).'.presentation', $values, $updatedBy); }

    private function groupHasStoredValues(string $group): bool
    {
        return Schema::hasTable('client_portal_settings')
            && ClientPortalSetting::query()->where('group_name', $group)->exists();
    }

    private function group(string $group, array $defaults): array
    {
        if (! Schema::hasTable('client_portal_settings')) return $defaults;
        $stored = Cache::rememberForever(self::CACHE_PREFIX.$group, function () use ($group): array {
            return ClientPortalSetting::query()->where('group_name', $group)->orderBy('key')->get()->mapWithKeys(fn (ClientPortalSetting $setting): array => [$setting->key => $this->decode($setting->value, $setting->type)])->all();
        });
        return array_replace($defaults, $stored);
    }

    private function updateGroup(string $group, array $values, ?int $updatedBy): void
    {
        DB::transaction(function () use ($group, $values, $updatedBy): void {
            foreach ($values as $key => $value) {
                [$storedValue, $type] = $this->encode($value);
                ClientPortalSetting::query()->updateOrCreate(['group_name' => $group, 'key' => (string) $key], ['value' => $storedValue, 'type' => $type, 'updated_by' => $updatedBy]);
            }
        });
        Cache::forget(self::CACHE_PREFIX.$group);
    }

    private function encode(mixed $value): array
    {
        if (is_array($value)) return [json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'json'];
        if (is_bool($value)) return [$value ? '1' : '0', 'boolean'];
        return [$value === null ? null : (string) $value, 'text'];
    }

    private function decode(?string $value, string $type): mixed
    {
        return match ($type) { 'json' => $value === null ? [] : json_decode($value, true, flags: JSON_THROW_ON_ERROR), 'boolean' => $value === '1', default => $value };
    }

    private function bool(mixed $value, bool $default): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
