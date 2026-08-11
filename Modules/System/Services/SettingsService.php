<?php

namespace Modules\System\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Modules\System\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings') && ! Schema::hasTable('wp_settings')) {
            return $default;
        }

        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default): mixed {
            if ($this->isLegacyHomepageKey($key) && Schema::hasTable('wp_settings')) {
                $setting = DB::table('wp_settings')->where('key', $key)->first();

                return $setting ? $this->decode($setting->value, $setting->type) : $default;
            }

            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting && Schema::hasTable('wp_settings')) {
                $setting = DB::table('wp_settings')->where('key', $key)->first();
            }

            return $setting ? $this->decode($setting->value, $setting->type) : $default;
        });
    }

    public function getGroup(string $group): array
    {
        if (! Schema::hasTable('settings') && ! Schema::hasTable('wp_settings')) {
            return [];
        }

        return Cache::rememberForever($this->groupCacheKey($group), function () use ($group): array {
            if ($group === 'homepage' && Schema::hasTable('wp_settings')) {
                return DB::table('wp_settings')
                    ->where('group_name', $group)
                    ->orderBy('key')
                    ->get()
                    ->mapWithKeys(fn (object $setting): array => [
                        $setting->key => $this->decode($setting->value, $setting->type),
                    ])->all();
            }

            return Setting::query()
                ->where('group_name', $group)
                ->orderBy('key')
                ->get()
                ->mapWithKeys(fn (Setting $setting): array => [
                    $setting->key => $this->decode($setting->value, $setting->type),
                ])->all();
        });
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        [$storedValue, $storedType] = $this->normalize($value, $type);

        if ($this->isLegacyHomepageKey($key)) {
            DB::table('wp_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $storedValue,
                    'group_name' => $group,
                    'type' => $storedType,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
            $this->forget($key);
            Cache::forget($this->groupCacheKey($group));

            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'group_name' => $group, 'type' => $storedType]
        );

        $this->forget($key);
        Cache::forget($this->groupCacheKey($group));
    }

    public function updateMany(array $values, string $group = 'general'): void
    {
        $filtered = array_diff_key($values, array_flip(['_token', '_method']));

        DB::transaction(function () use ($filtered, $group): void {
            foreach ($filtered as $key => $value) {
                [$storedValue, $storedType] = $this->normalize($value);
                $this->persist((string) $key, $storedValue, $storedType, $group);
            }
        });

        foreach (array_keys($filtered) as $key) {
            $this->forget((string) $key);
        }
        Cache::forget($this->groupCacheKey($group));
    }

    public function updateGroup(string $group, array $values): void
    {
        $this->updateMany(
            collect($values)->mapWithKeys(fn ($value, $key): array => ["{$group}.{$key}" => $value])->all(),
            $group,
        );
    }

    private function decode(mixed $value, string $type): mixed
    {
        if ($type !== 'json') {
            return $value;
        }

        return json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
    }

    private function persist(string $key, mixed $value, string $type, string $group): void
    {
        if ($this->isLegacyHomepageKey($key)) {
            DB::table('wp_settings')->updateOrInsert(
                ['key' => $key],
                [
                    'value' => $value,
                    'group_name' => $group,
                    'type' => $type,
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );

            return;
        }

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group_name' => $group, 'type' => $type]
        );
    }

    private function normalize(mixed $value, string $type = 'text'): array
    {
        if (is_array($value)) {
            return [json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'json'];
        }

        return [$value, $type];
    }

    private function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
        Cache::forget('wp_opt_'.$key);
        Cache::forget('setting_'.$key);
    }

    private function cacheKey(string $key): string
    {
        return 'system.setting.'.$key;
    }

    private function groupCacheKey(string $group): string
    {
        return 'system.settings.group.'.$group;
    }

    private function isLegacyHomepageKey(string $key): bool
    {
        return str_starts_with($key, 'home_');
    }
}
