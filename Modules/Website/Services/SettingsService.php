<?php

namespace Modules\Website\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\Setting;

class SettingsService
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default) {
            $setting = Setting::query()->where('key', $key)->first();

            if (! $setting) {
                return $default;
            }

            $decoded = json_decode((string) $setting->value, true);
            if ($setting->type === 'json' || (is_array($decoded) && json_last_error() === JSON_ERROR_NONE)) {
                return $decoded ?? $default;
            }

            return $setting->value;
        });
    }

    public function getGroup(string $group): array
    {
        return Setting::query()
            ->where('group_name', $group)
            ->get()
            ->mapWithKeys(function (Setting $setting): array {
                $key = str_starts_with($setting->key, $setting->group_name.'.')
                    ? substr($setting->key, strlen($setting->group_name) + 1)
                    : $setting->key;

                return [$key => $this->get($setting->key)];
            })
            ->all();
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        [$storedValue, $storedType] = $this->normalizeValue($value, $type);

        Setting::query()->updateOrCreate(
            ['key' => $key],
            ['value' => $storedValue, 'group_name' => $group, 'type' => $storedType]
        );

        $this->forget($key);
    }

    public function updateMany(array $data, string $group = 'general'): void
    {
        $keys = array_keys(array_diff_key($data, array_flip(['_token', '_method'])));

        DB::transaction(function () use ($data, $group): void {
            foreach ($data as $key => $value) {
                if (in_array($key, ['_token', '_method'], true)) {
                    continue;
                }

                [$storedValue, $storedType] = $this->normalizeValue($value);
                Setting::query()->updateOrCreate(
                    ['key' => $key],
                    ['value' => $storedValue, 'group_name' => $group, 'type' => $storedType]
                );
            }
        });

        foreach ($keys as $key) {
            $this->forget((string) $key);
        }
    }

    public function updateGroup(string $group, array $data): void
    {
        $this->updateMany(
            collect($data)->mapWithKeys(fn ($value, $key) => ["{$group}.{$key}" => $value])->all(),
            $group
        );
    }

    private function normalizeValue(mixed $value, string $type = 'text'): array
    {
        if (is_array($value)) {
            return [json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'json'];
        }

        return [$value, $type];
    }

    private function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
        Cache::forget('setting_'.$key);
    }

    private function cacheKey(string $key): string
    {
        return 'wp_opt_'.$key;
    }
}
