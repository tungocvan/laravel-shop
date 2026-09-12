<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Modules\System\Services\Cloud\GoogleDrivePortableFileService;
use RuntimeException;

class MenuSnapshotCloudSyncService
{
    public const CLOUD_PATH = 'Admin/Menu/menus.json';

    public function __construct(private readonly GoogleDrivePortableFileService $cloudFiles) {}

    public function localPath(): string
    {
        return storage_path('app/menu/menus.json');
    }

    public function pushLocalSnapshot(): array
    {
        $path = $this->localPath();

        if (! File::exists($path) || ! is_readable($path)) {
            throw new RuntimeException('Snapshot menu local chưa tồn tại hoặc không đọc được.');
        }

        $content = File::get($path);
        $this->assertValidSnapshot($content);

        return $this->cloudFiles->put(self::CLOUD_PATH, $content, 'application/json');
    }

    public function pushLocalSnapshotBestEffort(): bool
    {
        try {
            $this->pushLocalSnapshot();

            return true;
        } catch (\Throwable $exception) {
            Log::warning('Menu snapshot Google Drive sync failed after export.', [
                'service' => static::class,
                'path' => self::CLOUD_PATH,
                'message' => $exception->getMessage(),
            ]);

            return false;
        }
    }

    public function pullToLocal(): array
    {
        $remote = $this->cloudFiles->get(self::CLOUD_PATH);
        $content = (string) ($remote['content'] ?? '');
        $this->assertValidSnapshot($content);

        $path = $this->localPath();
        $directory = dirname($path);
        File::ensureDirectoryExists($directory);

        $temporary = $path.'.sync.tmp';
        File::put($temporary, $content, true);

        if (! File::move($temporary, $path)) {
            File::delete($temporary);
            throw new RuntimeException('Không thể đồng bộ snapshot menu về local.');
        }

        unset($remote['content']);

        return array_merge($remote, [
            'local_path' => $path,
            'synced_at' => now()->toIso8601String(),
        ]);
    }

    public function status(): array
    {
        $localPath = $this->localPath();
        $drive = $this->cloudFiles->status();
        $localExists = File::exists($localPath);

        return [
            'local_exists' => $localExists,
            'local_modified_at' => $localExists ? date(DATE_ATOM, (int) File::lastModified($localPath)) : null,
            'local_size' => $localExists ? File::size($localPath) : null,
            'cloud_connected' => (bool) ($drive['connected'] ?? false),
            'cloud_folder' => (string) ($drive['folder_name'] ?? 'Laravel-Backup'),
            'cloud_path' => self::CLOUD_PATH,
        ];
    }

    private function assertValidSnapshot(string $content): void
    {
        if (trim($content) === '') {
            throw new RuntimeException('Snapshot menu rỗng.');
        }

        try {
            $items = json_decode($content, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new RuntimeException('Snapshot menu JSON không hợp lệ.', previous: $exception);
        }

        if (! is_array($items)) {
            throw new RuntimeException('Snapshot menu phải là một JSON array.');
        }

        $this->validateNodes($items);
    }

    private function validateNodes(array $items): void
    {
        foreach ($items as $item) {
            if (! is_array($item) || trim((string) ($item['name'] ?? '')) === '') {
                throw new RuntimeException('Snapshot menu chứa item không hợp lệ.');
            }

            if (array_key_exists('children', $item) && ! is_array($item['children'])) {
                throw new RuntimeException('Snapshot menu có children không hợp lệ.');
            }

            if (($item['children'] ?? []) !== []) {
                $this->validateNodes($item['children']);
            }
        }
    }
}
