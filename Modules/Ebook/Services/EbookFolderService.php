<?php

namespace Modules\Ebook\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Ebook\Models\EbookFolder;

class EbookFolderService
{
    public function tree(): Collection
    {
        return EbookFolder::query()
            ->whereNull('parent_id')
            ->with('childrenRecursive')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function options(?int $excludeId = null): Collection
    {
        $query = EbookFolder::query()->orderBy('name');

        if ($excludeId !== null) {
            $excluded = $this->descendantIds($excludeId);
            $excluded[] = $excludeId;
            $query->whereNotIn('id', array_values(array_unique($excluded)));
        }

        return $query->get(['id', 'parent_id', 'name']);
    }

    public function find(int $id): EbookFolder
    {
        return EbookFolder::query()->findOrFail($id);
    }

    public function create(array $data): EbookFolder
    {
        $payload = $this->normalizePayload($data);
        $this->assertUniqueSiblingSlug($payload['parent_id'], $payload['slug']);
        $relativePath = $this->pathFor($payload['parent_id'], $payload['slug']);
        $disk = Storage::disk($this->disk());

        if ($disk->exists($relativePath)) {
            throw ValidationException::withMessages(['name' => 'Thư mục vật lý đã tồn tại.']);
        }

        $disk->makeDirectory($relativePath);

        try {
            return DB::transaction(fn (): EbookFolder => EbookFolder::query()->create($payload));
        } catch (\Throwable $e) {
            $disk->deleteDirectory($relativePath);
            throw $e;
        }
    }

    public function update(int $id, array $data): EbookFolder
    {
        $folder = $this->find($id);
        $payload = $this->normalizePayload($data);
        $this->assertValidParent($folder, $payload['parent_id']);
        $this->assertUniqueSiblingSlug($payload['parent_id'], $payload['slug'], $folder->id);

        $oldPath = $this->pathForFolder($folder);
        $newPath = $this->pathFor($payload['parent_id'], $payload['slug']);
        $disk = Storage::disk($this->disk());

        if ($oldPath !== $newPath && $disk->exists($newPath)) {
            throw ValidationException::withMessages(['name' => 'Thư mục đích đã tồn tại.']);
        }

        if ($oldPath !== $newPath && $disk->exists($oldPath) && ! $disk->move($oldPath, $newPath)) {
            throw ValidationException::withMessages(['name' => 'Không thể di chuyển thư mục vật lý.']);
        }

        try {
            DB::transaction(function () use ($folder, $payload): void {
                $folder->update($payload);
            });
        } catch (\Throwable $e) {
            if ($oldPath !== $newPath && $disk->exists($newPath) && ! $disk->exists($oldPath)) {
                $disk->move($newPath, $oldPath);
            }
            throw $e;
        }

        return $folder->fresh();
    }

    public function delete(int $id): void
    {
        $folder = $this->find($id);

        if ($folder->children()->exists()) {
            throw ValidationException::withMessages(['folder' => 'Không thể xóa thư mục đang có thư mục con.']);
        }

        $path = $this->pathForFolder($folder);
        $disk = Storage::disk($this->disk());

        if ($disk->exists($path) && $disk->allFiles($path) !== []) {
            throw ValidationException::withMessages(['folder' => 'Không thể xóa thư mục đang có file.']);
        }

        DB::transaction(fn () => $folder->delete());
        $disk->deleteDirectory($path);
    }

    private function normalizePayload(array $data): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = Str::slug((string) ($data['slug'] ?? $name));

        if ($name === '' || $slug === '') {
            throw ValidationException::withMessages(['name' => 'Tên thư mục không hợp lệ.']);
        }

        return [
            'parent_id' => filled($data['parent_id'] ?? null) ? (int) $data['parent_id'] : null,
            'name' => $name,
            'slug' => $slug,
            'description' => filled($data['description'] ?? null) ? trim((string) $data['description']) : null,
            'sort_order' => max(0, (int) ($data['sort_order'] ?? 0)),
            'is_active' => (bool) ($data['is_active'] ?? true),
        ];
    }

    private function assertValidParent(EbookFolder $folder, ?int $parentId): void
    {
        if ($parentId === null) {
            return;
        }

        if ($parentId === (int) $folder->id || in_array($parentId, $this->descendantIds((int) $folder->id), true)) {
            throw ValidationException::withMessages(['parent_id' => 'Không thể chuyển thư mục vào chính nó hoặc thư mục con.']);
        }

        EbookFolder::query()->findOrFail($parentId);
    }

    private function assertUniqueSiblingSlug(?int $parentId, string $slug, ?int $ignoreId = null): void
    {
        $query = EbookFolder::query()->where('slug', $slug);
        $parentId === null ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);

        if ($ignoreId !== null) {
            $query->whereKeyNot($ignoreId);
        }

        if ($query->exists()) {
            throw ValidationException::withMessages(['slug' => 'Slug đã tồn tại trong cùng thư mục cha.']);
        }
    }

    private function descendantIds(int $id): array
    {
        $ids = [];
        $queue = [$id];

        while ($queue !== []) {
            $parent = array_shift($queue);
            $children = EbookFolder::query()->where('parent_id', $parent)->pluck('id')->map(fn ($value) => (int) $value)->all();
            $ids = array_merge($ids, $children);
            $queue = array_merge($queue, $children);
        }

        return $ids;
    }

    private function pathFor(?int $parentId, string $slug): string
    {
        $segments = [$slug];
        $parent = $parentId ? EbookFolder::query()->findOrFail($parentId) : null;

        while ($parent !== null) {
            array_unshift($segments, $parent->slug);
            $parent = $parent->parent()->first();
        }

        return trim($this->root().'/'.implode('/', $segments), '/');
    }

    private function pathForFolder(EbookFolder $folder): string
    {
        return $this->pathFor($folder->parent_id ? (int) $folder->parent_id : null, $folder->slug);
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
