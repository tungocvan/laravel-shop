<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookDocument;
use Modules\Ebook\Models\EbookFolder;

class EbookSyncService
{
    public function preview(): array
    {
        $disk = Storage::disk($this->disk());
        $root = $this->root();
        $directories = collect($disk->allDirectories($root))
            ->filter(fn (string $path): bool => $this->isInsideRoot($path))
            ->sortBy(fn (string $path): int => substr_count($path, '/'))
            ->values();
        $files = collect($disk->allFiles($root))
            ->filter(fn (string $path): bool => $this->isInsideRoot($path) && strtolower(pathinfo($path, PATHINFO_EXTENSION)) === 'md')
            ->values();

        $foldersByPath = $this->folderPathMap();
        $documentsByPath = EbookDocument::query()->get()->keyBy('file_path');

        $newFolders = $directories
            ->reject(fn (string $path): bool => isset($foldersByPath[$path]))
            ->map(fn (string $path): array => ['key' => 'folder:new:'.sha1($path), 'path' => $path])
            ->values()->all();

        $missingFolders = collect($foldersByPath)
            ->keys()
            ->reject(fn (string $path): bool => $disk->exists($path))
            ->map(fn (string $path): array => ['key' => 'folder:missing:'.sha1($path), 'path' => $path, 'id' => $foldersByPath[$path]->id])
            ->values()->all();

        $newFiles = [];
        $changed = [];
        foreach ($files as $path) {
            $document = $documentsByPath->get($path);
            $content = (string) $disk->get($path);
            $hash = hash('sha256', $content);
            $mtime = $this->lastModified($path);

            if (! $document) {
                $newFiles[] = ['key' => 'file:new:'.sha1($path), 'path' => $path, 'hash' => $hash, 'mtime' => $mtime];
                continue;
            }

            if ($document->content_hash !== $hash || (int) ($document->file_mtime ?? 0) !== $mtime) {
                $changed[] = [
                    'key' => 'file:changed:'.$document->id,
                    'id' => (int) $document->id,
                    'path' => $path,
                    'old_hash' => $document->content_hash,
                    'hash' => $hash,
                    'mtime' => $mtime,
                ];
            }
        }

        $missingDocuments = $documentsByPath
            ->reject(fn (EbookDocument $document): bool => $disk->exists($document->file_path))
            ->map(fn (EbookDocument $document): array => [
                'key' => 'file:missing:'.$document->id,
                'id' => (int) $document->id,
                'path' => $document->file_path,
                'hash' => $document->content_hash,
                'title' => $document->title,
            ])->values()->all();

        [$moves, $ambiguous, $newFiles, $missingDocuments] = $this->detectMoves($newFiles, $missingDocuments);

        return [
            'new_folders' => $newFolders,
            'new_files' => array_values($newFiles),
            'changed' => $changed,
            'missing_folders' => $missingFolders,
            'missing_files' => array_values($missingDocuments),
            'moves' => $moves,
            'ambiguous' => $ambiguous,
            'summary' => [
                'new' => count($newFolders) + count($newFiles),
                'changed' => count($changed),
                'missing' => count($missingFolders) + count($missingDocuments),
                'moves' => count($moves),
                'ambiguous' => count($ambiguous),
            ],
        ];
    }

    public function applyConfirmed(array $confirmedKeys): array
    {
        $confirmedKeys = array_values(array_unique(array_filter(array_map('strval', $confirmedKeys))));
        if ($confirmedKeys === []) {
            throw ValidationException::withMessages(['sync' => 'Chưa chọn thay đổi nào để đồng bộ.']);
        }

        $fresh = $this->preview();
        $available = collect([
            ...$fresh['new_folders'],
            ...$fresh['new_files'],
            ...$fresh['changed'],
            ...$fresh['moves'],
        ])->keyBy('key');

        $applied = [];
        $skipped = [];
        $errors = [];

        DB::transaction(function () use ($confirmedKeys, $available, &$applied, &$skipped, &$errors): void {
            foreach ($confirmedKeys as $key) {
                $item = $available->get($key);
                if (! $item) {
                    $skipped[] = ['key' => $key, 'reason' => 'Thay đổi không còn hợp lệ sau khi re-scan.'];
                    continue;
                }

                try {
                    if (str_starts_with($key, 'folder:new:')) {
                        $this->applyNewFolder($item['path']);
                    } elseif (str_starts_with($key, 'file:new:')) {
                        $this->applyNewFile($item['path'], $item['hash'], (int) $item['mtime']);
                    } elseif (str_starts_with($key, 'file:changed:')) {
                        EbookDocument::query()->whereKey($item['id'])->update([
                            'content_hash' => $item['hash'],
                            'file_mtime' => $item['mtime'],
                        ]);
                    } elseif (str_starts_with($key, 'file:move:')) {
                        $this->applyMove($item);
                    }
                    $applied[] = $key;
                } catch (\Throwable $e) {
                    $errors[] = ['key' => $key, 'message' => $e->getMessage()];
                }
            }
        });

        return ['applied' => $applied, 'skipped' => $skipped, 'errors' => $errors, 'preview' => $this->preview()];
    }

    private function applyNewFolder(string $path): EbookFolder
    {
        if (! $this->isInsideRoot($path)) {
            throw ValidationException::withMessages(['sync' => 'Đường dẫn thư mục không hợp lệ.']);
        }

        $relative = trim(Str::after($path, $this->root()), '/');
        $segments = array_values(array_filter(explode('/', $relative)));
        $parentId = null;
        $folder = null;

        foreach ($segments as $segment) {
            $slug = Str::slug($segment);
            $query = EbookFolder::query()->where('slug', $slug);
            $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
            $folder = $query->first();
            if (! $folder) {
                $folder = EbookFolder::query()->create([
                    'parent_id' => $parentId,
                    'name' => Str::headline($segment),
                    'slug' => $slug,
                    'sort_order' => 0,
                    'is_active' => true,
                ]);
            }
            $parentId = (int) $folder->id;
        }

        return $folder ?? throw ValidationException::withMessages(['sync' => 'Không thể xác định thư mục.']);
    }

    private function applyNewFile(string $path, string $hash, int $mtime): EbookDocument
    {
        $folderPath = dirname($path);
        $folder = $this->folderForPath($folderPath) ?? $this->applyNewFolder($folderPath);
        $disk = Storage::disk($this->disk());
        $content = (string) $disk->get($path);
        $fileName = basename($path);
        $title = $this->extractTitle($content) ?: Str::headline(pathinfo($fileName, PATHINFO_FILENAME));
        $slug = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));

        $document = EbookDocument::query()->create([
            'folder_id' => $folder->id,
            'title' => $title,
            'slug' => $slug,
            'file_name' => $fileName,
            'file_path' => $path,
            'source_type' => 'file',
            'sort_order' => 0,
            'is_active' => true,
            'is_favorite' => false,
            'content_hash' => $hash,
            'file_mtime' => $mtime,
        ]);

        if (auth('admin')->check()) {
            $document->viewers()->syncWithoutDetaching([(int) auth('admin')->id()]);
        }

        return $document;
    }

    private function applyMove(array $item): void
    {
        $folderPath = dirname($item['to']);
        $folder = $this->folderForPath($folderPath) ?? $this->applyNewFolder($folderPath);
        $fileName = basename($item['to']);
        $slug = Str::slug(pathinfo($fileName, PATHINFO_FILENAME));

        EbookDocument::query()->whereKey($item['id'])->update([
            'folder_id' => $folder->id,
            'slug' => $slug,
            'file_name' => $fileName,
            'file_path' => $item['to'],
            'content_hash' => $item['hash'],
            'file_mtime' => $item['mtime'],
        ]);
    }

    private function detectMoves(array $newFiles, array $missingDocuments): array
    {
        $moves = [];
        $ambiguous = [];
        $consumedNew = [];
        $consumedMissing = [];

        foreach ($missingDocuments as $missingIndex => $missing) {
            if (! filled($missing['hash'])) {
                continue;
            }
            $matches = [];
            foreach ($newFiles as $newIndex => $new) {
                if ($new['hash'] === $missing['hash']) {
                    $matches[$newIndex] = $new;
                }
            }

            if (count($matches) === 1) {
                $newIndex = array_key_first($matches);
                if (isset($consumedNew[$newIndex])) {
                    $ambiguous[] = ['key' => 'file:ambiguous:'.$missing['id'], 'missing' => $missing, 'candidates' => array_values($matches)];
                    continue;
                }
                $new = $matches[$newIndex];
                $moves[] = [
                    'key' => 'file:move:'.$missing['id'].':'.sha1($new['path']),
                    'id' => $missing['id'],
                    'from' => $missing['path'],
                    'to' => $new['path'],
                    'hash' => $new['hash'],
                    'mtime' => $new['mtime'],
                ];
                $consumedNew[$newIndex] = true;
                $consumedMissing[$missingIndex] = true;
            } elseif (count($matches) > 1) {
                $ambiguous[] = ['key' => 'file:ambiguous:'.$missing['id'], 'missing' => $missing, 'candidates' => array_values($matches)];
            }
        }

        foreach (array_keys($consumedNew) as $index) {
            unset($newFiles[$index]);
        }
        foreach (array_keys($consumedMissing) as $index) {
            unset($missingDocuments[$index]);
        }

        return [$moves, $ambiguous, $newFiles, $missingDocuments];
    }

    private function folderPathMap(): array
    {
        $map = [];
        foreach (EbookFolder::query()->get() as $folder) {
            $map[$this->folderPath($folder)] = $folder;
        }
        return $map;
    }

    private function folderForPath(string $path): ?EbookFolder
    {
        return $this->folderPathMap()[$path] ?? null;
    }

    private function folderPath(EbookFolder $folder): string
    {
        $segments = [$folder->slug];
        $parent = $folder->parent()->first();
        while ($parent !== null) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent()->first();
        }
        return trim($this->root().'/'.implode('/', $segments), '/');
    }

    private function extractTitle(string $content): ?string
    {
        foreach (preg_split('/\R/', $content) ?: [] as $line) {
            if (preg_match('/^#\s+(.+)$/', trim($line), $matches) === 1) {
                return trim($matches[1]);
            }
        }
        return null;
    }

    private function lastModified(string $path): int
    {
        try {
            return (int) Storage::disk($this->disk())->lastModified($path);
        } catch (\Throwable) {
            return 0;
        }
    }

    private function isInsideRoot(string $path): bool
    {
        $normalized = trim(str_replace('\\', '/', $path), '/');
        $root = $this->root();
        return $normalized === $root || str_starts_with($normalized, $root.'/');
    }

    private function disk(): string
    {
        return (string) config('ebook.ebook.disk', 'local');
    }

    private function root(): string
    {
        return trim((string) config('ebook.ebook.root', 'ebooks'), '/');
    }
}
