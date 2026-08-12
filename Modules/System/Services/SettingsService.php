<?php

namespace Modules\System\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use InvalidArgumentException;
use Modules\System\Models\Setting;
use Throwable;

class SettingsService
{
    private const GENERAL_KEYS = ['site_name', 'site_email', 'site_hotline', 'site_address'];
    private const IMAGE_KEYS = ['site_logo', 'site_favicon'];

    public function getGeneral(): array
    {
        $values = [];
        foreach (self::GENERAL_KEYS as $key) {
            $values[$key] = Setting::getValue($key, '');
        }
        return $values;
    }

    public function saveGeneral(array $values): void
    {
        DB::transaction(function () use ($values): void {
            foreach (self::GENERAL_KEYS as $key) {
                $value = $values[$key] ?? null;
                $value = is_string($value) ? trim($value) : $value;
                Setting::setValue($key, $value === '' && $key !== 'site_name' ? null : $value);
            }
        });
    }

    public function getImages(): array
    {
        return [
            'site_logo' => Setting::getValue('site_logo'),
            'site_favicon' => Setting::getValue('site_favicon'),
        ];
    }

    public function replaceImage(string $type, UploadedFile $upload): string
    {
        $key = $this->imageKey($type);
        $disk = Storage::disk('public');
        $oldPath = Setting::getValue($key);
        $newPath = $upload->store('settings', 'public');

        try {
            Setting::setValue($key, $newPath);
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
        $oldPath = Setting::getValue($key);

        Setting::setValue($key, null);
        if ($oldPath) {
            $disk->delete($oldPath);
        }
    }

    private function imageKey(string $type): string
    {
        $key = match ($type) {
            'logo' => 'site_logo',
            'favicon' => 'site_favicon',
            default => throw new InvalidArgumentException('Unsupported settings image type.'),
        };

        if (!in_array($key, self::IMAGE_KEYS, true)) {
            throw new InvalidArgumentException('Unsupported settings image key.');
        }
        return $key;
    }
}
