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

    public function pwaLogin(): array
    {
        $defaults = config('clientportal.pwa.login', []);
        $settings = $this->group('pwa.login', $defaults);

        $settings['show_intro_panel'] = $this->bool($settings['show_intro_panel'] ?? true, true);
        $settings['feature_cards'] = collect($settings['feature_cards'] ?? [])
            ->filter(fn ($card): bool => is_array($card))
            ->map(fn (array $card): array => [
                'enabled' => $this->bool($card['enabled'] ?? true, true),
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
            ])
            ->values()
            ->all();

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
        $defaults = [
            'enabled' => true,
            'name' => $application['name'],
            'description' => $application['description'],
            'sort_order' => $application['sort_order'],
        ];

        $settings = $this->group('application.'.$application['key'].'.presentation', $defaults);
        $settings['enabled'] = $this->bool($settings['enabled'] ?? true, true);
        $settings['sort_order'] = (int) ($settings['sort_order'] ?? $application['sort_order']);

        return $settings;
    }

    public function presentApplications(Collection $applications): Collection
    {
        return $applications
            ->map(function (array $application): array {
                $presentation = $this->applicationPresentation($application);

                return array_replace($application, [
                    'presentation_enabled' => $presentation['enabled'],
                    'name' => trim((string) $presentation['name']),
                    'description' => trim((string) $presentation['description']),
                    'sort_order' => $presentation['sort_order'],
                ]);
            })
            ->filter(fn (array $application): bool => $application['presentation_enabled'])
            ->sortBy(fn (array $application): array => [$application['sort_order'], $application['name']])
            ->values();
    }

    public function updatePwaGeneral(array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('pwa.general', $values, $updatedBy);
    }

    public function updatePwaLogin(array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('pwa.login', $values, $updatedBy);
    }

    public function updatePwaLauncher(array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('pwa.launcher', $values, $updatedBy);
    }

    public function updateApplicationPresentation(string $applicationKey, array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('application.'.trim($applicationKey).'.presentation', $values, $updatedBy);
    }

    private function group(string $group, array $defaults): array
    {
        if (! Schema::hasTable('client_portal_settings')) {
            return $defaults;
        }

        $stored = Cache::rememberForever(self::CACHE_PREFIX.$group, function () use ($group): array {
            return ClientPortalSetting::query()
                ->where('group_name', $group)
                ->orderBy('key')
                ->get()
                ->mapWithKeys(fn (ClientPortalSetting $setting): array => [
                    $setting->key => $this->decode($setting->value, $setting->type),
                ])
                ->all();
        });

        return array_replace($defaults, $stored);
    }

    private function updateGroup(string $group, array $values, ?int $updatedBy): void
    {
        DB::transaction(function () use ($group, $values, $updatedBy): void {
            foreach ($values as $key => $value) {
                [$storedValue, $type] = $this->encode($value);

                ClientPortalSetting::query()->updateOrCreate(
                    [
                        'group_name' => $group,
                        'key' => (string) $key,
                    ],
                    [
                        'value' => $storedValue,
                        'type' => $type,
                        'updated_by' => $updatedBy,
                    ]
                );
            }
        });

        Cache::forget(self::CACHE_PREFIX.$group);
    }

    private function encode(mixed $value): array
    {
        if (is_array($value)) {
            return [json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'json'];
        }

        if (is_bool($value)) {
            return [$value ? '1' : '0', 'boolean'];
        }

        return [$value === null ? null : (string) $value, 'text'];
    }

    private function decode(?string $value, string $type): mixed
    {
        return match ($type) {
            'json' => $value === null ? [] : json_decode($value, true, flags: JSON_THROW_ON_ERROR),
            'boolean' => $value === '1',
            default => $value,
        };
    }

    private function bool(mixed $value, bool $default): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? $default;
    }
}
