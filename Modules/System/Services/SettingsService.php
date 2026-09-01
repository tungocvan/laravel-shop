<?php

namespace Modules\System\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\System\Models\Setting;
use Throwable;

class SettingsService
{
    private const GENERAL_KEYS = ['site_name', 'site_email', 'site_hotline', 'site_address'];
    private const IMAGE_KEYS = ['site_logo', 'site_favicon'];

    public function get(string $key, mixed $default = null): mixed
    {
        if (! Schema::hasTable('settings')) {
            return $default;
        }

        return Cache::rememberForever($this->cacheKey($key), function () use ($key, $default): mixed {
            $setting = Setting::query()->where('key', $key)->first();

            return $setting ? $this->decode($setting->value, $setting->type) : $default;
        });
    }

    public function getGroup(string $group): array
    {
        if (! Schema::hasTable('settings')) {
            return [];
        }

        return Cache::rememberForever($this->groupCacheKey($group), function () use ($group): array {
            return Setting::query()->where('group_name', $group)->orderBy('key')->get()
                ->mapWithKeys(fn (Setting $setting): array => [$setting->key => $this->decode($setting->value, $setting->type)])->all();
        });
    }

    public function set(string $key, mixed $value, string $group = 'general', string $type = 'text'): void
    {
        [$storedValue, $storedType] = $this->normalize($value, $type);

        Setting::query()->updateOrCreate(['key' => $key], ['value' => $storedValue, 'group_name' => $group, 'type' => $storedType]);
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
        $this->updateMany(collect($values)->mapWithKeys(fn ($value, $key): array => ["{$group}.{$key}" => $value])->all(), $group);
    }

    public function getGeneral(): array
    {
        $values = [];
        foreach (self::GENERAL_KEYS as $key) {
            $values[$key] = $this->get($key, '');
        }
        return $values;
    }

    public function saveGeneral(array $values): void
    {
        $allowed = array_intersect_key($values, array_flip(self::GENERAL_KEYS));
        foreach ($allowed as $key => $value) {
            $allowed[$key] = is_string($value) ? trim($value) : $value;
        }
        $this->updateMany($allowed, 'general');
    }

    public function getImages(): array
    {
        return ['site_logo' => $this->get('site_logo'), 'site_favicon' => $this->get('site_favicon')];
    }

    public function replaceImage(string $type, UploadedFile $upload): string
    {
        $key = $this->imageKey($type);
        $disk = Storage::disk('public');
        $oldPath = $this->get($key);
        $newPath = $upload->store('settings', 'public');

        try {
            $this->set($key, $newPath, 'general');
        } catch (Throwable $e) {
            $disk->delete($newPath);
            throw $e;
        }

        if ($oldPath && $oldPath !== $newPath) {
            $disk->delete($oldPath);
        }
        return $newPath;
    }

    public function removeImage(string $type): void
    {
        $key = $this->imageKey($type);
        $disk = Storage::disk('public');
        $oldPath = $this->get($key);
        $this->set($key, null, 'general');
        if ($oldPath) {
            $disk->delete($oldPath);
        }
    }

    private function decode(mixed $value, string $type): mixed
    {
        return $type !== 'json' ? $value : json_decode((string) $value, true, flags: JSON_THROW_ON_ERROR);
    }

    private function persist(string $key, mixed $value, string $type, string $group): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value, 'group_name' => $group, 'type' => $type]);
    }

    private function normalize(mixed $value, string $type = 'text'): array
    {
        return is_array($value)
            ? [json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR), 'json']
            : [$value, $type];
    }

    private function forget(string $key): void
    {
        Cache::forget($this->cacheKey($key));
        Cache::forget('wp_opt_'.$key);
        Cache::forget('setting_'.$key);
    }

    private function cacheKey(string $key): string { return 'system.setting.'.$key; }
    private function groupCacheKey(string $group): string { return 'system.settings.group.'.$group; }

    private function imageKey(string $type): string
    {
        $key = match ($type) {
            'logo' => 'site_logo', 'favicon' => 'site_favicon',
            default => throw new InvalidArgumentException('Unsupported settings image type.'),
        };
        if (! in_array($key, self::IMAGE_KEYS, true)) {
            throw new InvalidArgumentException('Unsupported settings image key.');
        }
        return $key;
    }
}
