<?php

namespace Modules\ClientPortal\Services;

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

        $settings['show_intro_panel'] = filter_var(
            $settings['show_intro_panel'] ?? true,
            FILTER_VALIDATE_BOOL,
            FILTER_NULL_ON_FAILURE
        ) ?? true;

        $settings['feature_cards'] = collect($settings['feature_cards'] ?? [])
            ->filter(fn ($card): bool => is_array($card))
            ->map(fn (array $card): array => [
                'enabled' => filter_var($card['enabled'] ?? true, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE) ?? true,
                'title' => trim((string) ($card['title'] ?? '')),
                'description' => trim((string) ($card['description'] ?? '')),
            ])
            ->values()
            ->all();

        return $settings;
    }

    public function updatePwaGeneral(array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('pwa.general', $values, $updatedBy);
    }

    public function updatePwaLogin(array $values, ?int $updatedBy = null): void
    {
        $this->updateGroup('pwa.login', $values, $updatedBy);
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

        return array_replace_recursive($defaults, $stored);
    }

    private function updateGroup(string $group, array $values, ?int $updatedBy): void
    {
        DB::transaction(function () use ($group, $values, $updatedBy): void {
            foreach ($values as $key => $value) {
                [$storedValue, $type] = $this->encode($value);

                ClientPortalSetting::query()->updateOrCreate(
                    ['key' => (string) $key],
                    [
                        'group_name' => $group,
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
}
