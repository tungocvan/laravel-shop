<?php

namespace Modules\System\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Modules\System\Models\Setting;
use RuntimeException;
use Throwable;

class CustomSettingsService
{
    private const IMAGE_ROOT = 'settings/custom';
    private const GALLERY_ROOT = 'settings/gallery';

    public function all(): array
    {
        return Setting::query()
            ->where('group_name', 'custom')
            ->orderBy('id')
            ->get()
            ->map(fn (Setting $setting): array => [
                'id' => $setting->id,
                'label' => $setting->label,
                'key' => $setting->key,
                'type' => $setting->type,
                'value' => $setting->value,
            ])
            ->all();
    }

    public function create(array $data, ?int $actorId = null): array
    {
        $key = Str::slug((string) ($data['key'] ?? ''), '_');
        if ($key === '') {
            throw new InvalidArgumentException('Invalid custom setting key.');
        }

        if (Setting::query()->where('key', $key)->exists()) {
            throw new InvalidArgumentException('Custom setting key already exists.');
        }

        $setting = DB::transaction(function () use ($data, $key): Setting {
            return Setting::query()->create([
                'label' => trim((string) $data['label']),
                'key' => $key,
                'type' => (string) $data['type'],
                'group_name' => 'custom',
                'value' => null,
            ]);
        });

        Cache::forget('setting_'.$setting->key);
        Log::notice('Custom setting created.', [
            'actor_id' => $actorId,
            'setting_id' => $setting->id,
            'key' => $setting->key,
            'type' => $setting->type,
        ]);

        return [
            'id' => $setting->id,
            'label' => $setting->label,
            'key' => $setting->key,
            'type' => $setting->type,
            'value' => $setting->value,
        ];
    }

    public function delete(int $settingId, ?int $actorId = null): void
    {
        $setting = $this->findCustom($settingId);
        $ownedFiles = $this->ownedFilesFor($setting);
        $key = $setting->key;
        $type = $setting->type;

        DB::transaction(function () use ($setting): void {
            $setting->delete();
        });

        foreach ($ownedFiles as $path) {
            $this->deleteOwnedPath($path);
        }

        Cache::forget('setting_'.$key);
        Log::notice('Custom setting deleted.', [
            'actor_id' => $actorId,
            'setting_id' => $settingId,
            'key' => $key,
            'type' => $type,
            'deleted_files' => count($ownedFiles),
        ]);
    }

    public function save(
        array $values,
        array $images = [],
        array $galleryUploads = [],
        ?int $actorId = null,
    ): void {
        $settings = Setting::query()
            ->where('group_name', 'custom')
            ->get()
            ->keyBy('id');

        $stagedFiles = [];
        $deleteAfterCommit = [];
        $updates = [];

        try {
            foreach ($settings as $id => $setting) {
                $id = (int) $id;

                if ($setting->type === 'image') {
                    $upload = $images[$id] ?? null;
                    if ($upload instanceof UploadedFile) {
                        $path = $upload->store(self::IMAGE_ROOT, 'public');
                        if (! is_string($path) || $path === '') {
                            throw new RuntimeException('Unable to store custom setting image.');
                        }

                        $stagedFiles[] = $path;
                        $updates[$id] = $path;

                        if (is_string($setting->value) && $this->isOwnedPath($setting->value, self::IMAGE_ROOT)) {
                            $deleteAfterCommit[] = $setting->value;
                        }
                    }

                    continue;
                }

                if ($setting->type === 'gallery') {
                    $persisted = $this->decodeGallery($setting->value);
                    $requested = array_values(array_filter(
                        is_array($values[$id] ?? null) ? $values[$id] : $persisted,
                        fn (mixed $path): bool => is_string($path) && in_array($path, $persisted, true),
                    ));

                    $removed = array_values(array_diff($persisted, $requested));
                    foreach ($removed as $path) {
                        if ($this->isOwnedPath($path, self::GALLERY_ROOT)) {
                            $deleteAfterCommit[] = $path;
                        }
                    }

                    $uploads = $galleryUploads[$id] ?? [];
                    if (! is_array($uploads)) {
                        $uploads = [];
                    }
                    if (count($uploads) > 20) {
                        throw new InvalidArgumentException('Too many gallery uploads.');
                    }

                    foreach ($uploads as $upload) {
                        if (! $upload instanceof UploadedFile) {
                            throw new InvalidArgumentException('Invalid gallery upload.');
                        }

                        $path = $upload->store(self::GALLERY_ROOT, 'public');
                        if (! is_string($path) || $path === '') {
                            throw new RuntimeException('Unable to store custom gallery image.');
                        }

                        $stagedFiles[] = $path;
                        $requested[] = $path;
                    }

                    $updates[$id] = json_encode(array_values(array_unique($requested)), JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
                    continue;
                }

                $updates[$id] = $values[$id] ?? null;
            }

            DB::transaction(function () use ($settings, $updates): void {
                foreach ($updates as $id => $value) {
                    /** @var Setting|null $setting */
                    $setting = $settings->get($id);
                    if (! $setting) {
                        continue;
                    }
                    $setting->update(['value' => $value]);
                }
            });
        } catch (Throwable $e) {
            foreach ($stagedFiles as $path) {
                $this->deleteOwnedPath($path);
            }

            Log::error('Custom settings save failed.', [
                'actor_id' => $actorId,
                'exception' => $e::class,
                'staged_files' => count($stagedFiles),
            ]);

            throw $e;
        }

        foreach (array_unique($deleteAfterCommit) as $path) {
            $this->deleteOwnedPath($path);
        }

        foreach ($settings as $setting) {
            Cache::forget('setting_'.$setting->key);
        }

        Log::notice('Custom settings saved.', [
            'actor_id' => $actorId,
            'settings_count' => count($updates),
            'new_files' => count($stagedFiles),
            'removed_files' => count(array_unique($deleteAfterCommit)),
        ]);
    }

    private function findCustom(int $settingId): Setting
    {
        $setting = Setting::query()
            ->where('group_name', 'custom')
            ->whereKey($settingId)
            ->first();

        if (! $setting) {
            throw new InvalidArgumentException('Custom setting not found.');
        }

        return $setting;
    }

    private function ownedFilesFor(Setting $setting): array
    {
        if ($setting->type === 'image' && is_string($setting->value) && $this->isOwnedPath($setting->value, self::IMAGE_ROOT)) {
            return [$setting->value];
        }

        if ($setting->type === 'gallery') {
            return array_values(array_filter(
                $this->decodeGallery($setting->value),
                fn (string $path): bool => $this->isOwnedPath($path, self::GALLERY_ROOT),
            ));
        }

        return [];
    }

    private function decodeGallery(mixed $value): array
    {
        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded)
            ? array_values(array_filter($decoded, 'is_string'))
            : [];
    }

    private function deleteOwnedPath(string $path): void
    {
        if (! $this->isOwnedPath($path, self::IMAGE_ROOT) && ! $this->isOwnedPath($path, self::GALLERY_ROOT)) {
            return;
        }

        $disk = Storage::disk('public');
        if ($disk->exists($path)) {
            $disk->delete($path);
        }
    }

    private function isOwnedPath(string $path, string $root): bool
    {
        $normalized = str_replace('\\', '/', trim($path));
        $root = trim($root, '/');

        return $normalized !== ''
            && ! str_contains($normalized, '..')
            && ! str_starts_with($normalized, '/')
            && str_starts_with($normalized, $root.'/');
    }
}
