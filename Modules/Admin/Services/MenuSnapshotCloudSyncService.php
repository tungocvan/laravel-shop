<?php

namespace Modules\Admin\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;
use Modules\System\Services\Cloud\GoogleDrivePortableFileService;
use RuntimeException;

class MenuSnapshotCloudSyncService
{
    public const CLOUD_DIRECTORY = 'Admin/Menu';

    public function __construct(private readonly GoogleDrivePortableFileService $cloudFiles) {}

    public function localPath(): string
    {
        return storage_path('app/menu/menus.json');
    }

    public function snapshotFileName(string $snapshotName): string
    {
        $snapshotName = trim($snapshotName);
        $snapshotName = preg_replace('/\.json\z/i', '', $snapshotName) ?? $snapshotName;
        $snapshotName = preg_replace('/\Amenus?[-_ ]*/i', '', $snapshotName) ?? $snapshotName;
        $slug = Str::slug($snapshotName);

        if ($slug === '' || strlen($slug) > 80) {
            throw new RuntimeException('Tên snapshot không hợp lệ.');
        }

        return 'menus-'.$slug.'.json';
    }

    public function snapshots(): array
    {
        return collect($this->cloudFiles->list(self::CLOUD_DIRECTORY))
            ->filter(fn (array $file): bool => $this->isAllowedSnapshotFile((string) ($file['name'] ?? '')))
            ->map(fn (array $file): array => [
                'id' => (string) ($file['id'] ?? ''),
                'name' => (string) ($file['name'] ?? ''),
                'size' => (int) ($file['size'] ?? 0),
                'modified_at' => $file['modified_at'] ?? null,
                'path' => self::CLOUD_DIRECTORY.'/'.(string) ($file['name'] ?? ''),
            ])
            ->values()
            ->all();
    }

    public function pushFullSnapshot(string $snapshotName): array
    {
        $content = $this->fullSnapshotContent();
        $this->assertValidSnapshot($content);
        $fileName = $this->snapshotFileName($snapshotName);

        return $this->cloudFiles->put(self::CLOUD_DIRECTORY.'/'.$fileName, $content, 'application/json');
    }

    public function pushFullSnapshotBestEffort(string $snapshotName): bool
    {
        try {
            $this->pushFullSnapshot($snapshotName);

            return true;
        } catch (\Throwable $exception) {
            $this->logSyncFailure($snapshotName, $exception);

            return false;
        }
    }

    public function pushSelectedSnapshot(string $snapshotName, array $menuIds): array
    {
        $content = $this->selectedSnapshotContent($menuIds);
        $this->assertValidSnapshot($content);
        $fileName = $this->snapshotFileName($snapshotName);

        return $this->cloudFiles->put(self::CLOUD_DIRECTORY.'/'.$fileName, $content, 'application/json');
    }

    public function pushSelectedSnapshotBestEffort(string $snapshotName, array $menuIds): bool
    {
        try {
            $this->pushSelectedSnapshot($snapshotName, $menuIds);

            return true;
        } catch (\Throwable $exception) {
            $this->logSyncFailure($snapshotName, $exception, [
                'selected_menu_count' => count($menuIds),
            ]);

            return false;
        }
    }

    public function renameSnapshot(string $snapshotFile, string $newSnapshotName): array
    {
        $snapshotFile = $this->assertAllowedSnapshotFile($snapshotFile);
        $newFileName = $this->snapshotFileName($newSnapshotName);

        return $this->cloudFiles->rename(
            self::CLOUD_DIRECTORY.'/'.$snapshotFile,
            $newFileName,
        );
    }

    public function deleteSnapshots(array $snapshotFiles): int
    {
        $files = array_values(array_unique(array_filter(array_map(
            fn (mixed $file): string => $this->assertAllowedSnapshotFile((string) $file),
            $snapshotFiles,
        ))));

        if ($files === []) {
            throw new RuntimeException('Chưa chọn snapshot để xóa.');
        }

        foreach ($files as $file) {
            $this->cloudFiles->delete(self::CLOUD_DIRECTORY.'/'.$file);
        }

        return count($files);
    }

    public function pullToLocal(string $snapshotFile): array
    {
        $snapshotFile = $this->assertAllowedSnapshotFile($snapshotFile);
        $remote = $this->cloudFiles->get(self::CLOUD_DIRECTORY.'/'.$snapshotFile);
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
            'source_file' => $snapshotFile,
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
            'cloud_path' => self::CLOUD_DIRECTORY,
        ];
    }

    private function fullSnapshotContent(): string
    {
        $menus = AdminMenu::menu()
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($menus->isEmpty()) {
            throw new RuntimeException('Không có menu hợp lệ để tạo snapshot.');
        }

        $roots = $menus->filter(fn (AdminMenu $menu): bool => $menu->parent_id === null)->values();
        $snapshot = $this->selectedSnapshotTree($roots, $menus);

        return json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }

    private function selectedSnapshotContent(array $menuIds): string
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn (mixed $id): string => trim((string) $id),
            $menuIds,
        ), static fn (string $id): bool => $id !== '')));

        if ($ids === []) {
            throw new RuntimeException('Chưa chọn menu để tạo snapshot.');
        }

        $menus = AdminMenu::menu()
            ->whereIn('id', $ids)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        if ($menus->isEmpty()) {
            throw new RuntimeException('Không có menu hợp lệ trong phạm vi đã chọn.');
        }

        $selectedIds = $menus->mapWithKeys(fn (AdminMenu $menu): array => [(string) $menu->getKey() => true]);
        $roots = $menus->filter(function (AdminMenu $menu) use ($selectedIds): bool {
            $parentId = $menu->parent_id;

            return $parentId === null || ! $selectedIds->has((string) $parentId);
        })->values();

        $snapshot = $this->selectedSnapshotTree($roots, $menus);

        return json_encode(
            $snapshot,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        ).PHP_EOL;
    }

    private function selectedSnapshotTree(Collection $menus, Collection $selectedMenus): array
    {
        return $menus->map(function (AdminMenu $menu) use ($selectedMenus): array {
            $children = $selectedMenus
                ->filter(fn (AdminMenu $candidate): bool => (string) $candidate->parent_id === (string) $menu->getKey())
                ->sortBy([['sort_order', 'asc'], ['id', 'asc']])
                ->values();

            return [
                'key' => $this->menuKey($menu),
                'name' => $menu->name,
                'url' => $menu->url,
                'icon' => $menu->icon,
                'can' => $menu->can,
                'is_active' => (bool) $menu->is_active,
                'children' => $this->selectedSnapshotTree($children, $selectedMenus),
            ];
        })->values()->all();
    }

    private function menuKey(AdminMenu $menu): string
    {
        $slug = trim((string) ($menu->slug ?? ''));

        return $slug !== '' ? $slug : (Str::slug($menu->name) ?: 'menu-'.$menu->getKey());
    }

    private function logSyncFailure(string $snapshotName, \Throwable $exception, array $context = []): void
    {
        Log::warning('Menu snapshot Google Drive sync failed after export.', array_merge([
            'service' => static::class,
            'directory' => self::CLOUD_DIRECTORY,
            'snapshot' => $snapshotName,
            'message' => $exception->getMessage(),
        ], $context));
    }

    private function assertAllowedSnapshotFile(string $snapshotFile): string
    {
        $snapshotFile = trim($snapshotFile);

        if (! $this->isAllowedSnapshotFile($snapshotFile)) {
            throw new RuntimeException('Tên file snapshot menu không hợp lệ.');
        }

        return $snapshotFile;
    }

    private function isAllowedSnapshotFile(string $snapshotFile): bool
    {
        return (bool) preg_match('/\Amenus(?:-[a-z0-9][a-z0-9-]{0,80})?\.json\z/', $snapshotFile);
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
