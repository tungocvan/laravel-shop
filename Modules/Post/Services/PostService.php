<?php

namespace Modules\Post\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Post\Models\Post;
use Modules\Post\Models\Tag;

class PostService
{
    public const STATUSES = ['published', 'draft', 'hidden'];

    public const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        return $this->adminQuery($filters)
            ->paginate($this->normalizePerPage($filters['per_page'] ?? 10));
    }

    public function currentPageIds(array $filters = []): array
    {
        return $this->paginateForAdmin($filters)
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function findForEdit(int $id): Post
    {
        return Post::query()
            ->with(['categories:id,name', 'tags:id,name'])
            ->findOrFail($id);
    }

    public function postCategoryOptions(): Collection
    {
        return Category::query()
            ->where('type', 'post')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function create(array $data): Post
    {
        return $this->savePost(null, $data);
    }

    public function update(int $id, array $data): Post
    {
        return $this->savePost($id, $data);
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id): void {
            Post::query()->whereKey($id)->firstOrFail()->delete();
        });
    }

    public function bulkDelete(array $ids): int
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return 0;
        }

        return DB::transaction(fn () => Post::query()->whereIn('id', $ids)->delete());
    }

    public function clone(int $id, ?int $userId = null): Post
    {
        return DB::transaction(function () use ($id, $userId): Post {
            $original = Post::query()
                ->with(['categories:id', 'tags:id'])
                ->lockForUpdate()
                ->findOrFail($id);

            $clone = $original->replicate();
            $clone->name = $original->name . ' (Copy)';
            $clone->slug = $this->uniqueSlug(Str::slug($clone->name));
            $clone->status = 'draft';
            $clone->views = 0;
            $clone->user_id = $userId ?: $original->user_id;
            $clone->published_at = null;
            $clone->created_at = now();
            $clone->updated_at = now();
            $clone->save();

            $clone->categories()->sync($original->categories->pluck('id')->all());
            $clone->tags()->sync($original->tags->pluck('id')->all());

            return $clone->load(['categories:id,name', 'tags:id,name']);
        });
    }

    public function importRow(array $data, string $mode = 'update_or_create'): Post
    {
        $slug = $this->normalizeSlug($data['slug'] ?? null, $data['name'] ?? null);
        $existing = Post::query()->where('slug', $slug)->first();

        if ($mode === 'skip_duplicate' && $existing) {
            return $existing;
        }

        if ($mode === 'create_only' && $existing) {
            throw ValidationException::withMessages([
                'slug' => "Slug {$slug} da ton tai.",
            ]);
        }

        $payload = array_merge($data, [
            'slug' => $slug,
            'category_ids' => $this->categoryIdsFromNames($data['categories'] ?? ''),
            'tags' => $data['tags'] ?? '',
        ]);

        return $existing
            ? $this->savePost((int) $existing->id, $payload)
            : $this->savePost(null, $payload);
    }

    public function exportRows(array $filters = [], ?array $ids = null, int $limit = 5000): Collection
    {
        $query = $this->adminQuery($filters)
            ->with(['tags:id,name'])
            ->limit($limit);

        $ids = $this->normalizeIds($ids ?? []);

        if ($ids !== []) {
            $query->whereIn('wp_posts.id', $ids);
        }

        return $query->get();
    }

    public function normalizePerPage(mixed $perPage): int
    {
        $perPage = (int) $perPage;

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    public function normalizeSlug(?string $slug, ?string $name = null): string
    {
        $slug = Str::slug($slug ?: $name);

        if ($slug === '') {
            throw ValidationException::withMessages([
                'slug' => 'Slug khong hop le.',
            ]);
        }

        return $slug;
    }

    public function normalizeTagNames(string|array|null $tags): array
    {
        if (is_array($tags)) {
            $values = $tags;
        } else {
            $values = explode(',', (string) $tags);
        }

        return collect($values)
            ->map(fn ($tag) => trim((string) $tag))
            ->filter()
            ->unique(fn ($tag) => Str::lower($tag))
            ->values()
            ->all();
    }

    private function savePost(?int $id, array $data): Post
    {
        $attributes = $this->postAttributes($data, $id);
        $categoryIds = $this->validCategoryIds($data['category_ids'] ?? []);
        $tagNames = $this->normalizeTagNames($data['tags'] ?? $data['inputTags'] ?? null);
        $newThumbnail = $data['new_thumbnail'] ?? null;
        $newPath = $newThumbnail instanceof UploadedFile
            ? $newThumbnail->store('posts', 'public')
            : null;

        if ($newPath) {
            $attributes['thumbnail'] = $newPath;
        }

        try {
            $oldThumbnail = null;

            $post = DB::transaction(function () use ($id, $attributes, $categoryIds, $tagNames, &$oldThumbnail): Post {
                if ($id) {
                    $post = Post::query()->lockForUpdate()->findOrFail($id);
                    $oldThumbnail = $post->thumbnail;
                    $post->update($attributes);
                } else {
                    $post = Post::query()->create($attributes);
                }

                $post->categories()->sync($categoryIds);
                $post->tags()->sync($this->tagIds($tagNames));

                return $post->load(['categories:id,name', 'tags:id,name']);
            });
        } catch (\Throwable $exception) {
            $this->deleteStoredThumbnail($newPath);

            throw $exception;
        }

        if ($newPath && isset($oldThumbnail)) {
            $this->deleteStoredThumbnail($oldThumbnail);
        }

        return $post;
    }

    private function postAttributes(array $data, ?int $id): array
    {
        $name = trim((string) ($data['name'] ?? ''));
        $slug = $this->normalizeSlug($data['slug'] ?? null, $name);
        $status = $data['status'] ?? 'draft';

        if ($name === '') {
            throw ValidationException::withMessages([
                'name' => 'Tieu de bai viet la bat buoc.',
            ]);
        }

        if (! in_array($status, self::STATUSES, true)) {
            throw ValidationException::withMessages([
                'status' => 'Trang thai bai viet khong hop le.',
            ]);
        }

        $duplicate = Post::query()
            ->where('slug', $slug)
            ->when($id, fn (Builder $query) => $query->whereKeyNot($id))
            ->exists();

        if ($duplicate) {
            throw ValidationException::withMessages([
                'slug' => 'Slug bai viet da ton tai.',
            ]);
        }

        $attributes = [
            'name' => $name,
            'slug' => $slug,
            'summary' => $data['summary'] ?? null,
            'content' => $data['content'] ?? null,
            'thumbnail' => $data['thumbnail'] ?? null,
            'status' => $status,
            'is_featured' => (bool) ($data['is_featured'] ?? false),
            'meta_title' => ($data['meta_title'] ?? null) ?: $name,
            'meta_description' => $data['meta_description'] ?? $data['summary'] ?? null,
            'user_id' => $data['user_id'] ?? null,
        ];

        if (! $id) {
            $attributes['published_at'] = $status === 'published' ? now() : null;
            $attributes['views'] = (int) ($data['views'] ?? 0);
        }

        return $attributes;
    }

    private function adminQuery(array $filters = []): Builder
    {
        return Post::query()
            ->with(['author:id,name', 'categories:id,name'])
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $nested) use ($search): void {
                    $nested->where('name', 'like', '%' . $search . '%')
                        ->orWhere('slug', 'like', '%' . $search . '%');
                });
            })
            ->when($filters['status'] ?? null, function (Builder $query, string $status): void {
                if (in_array($status, self::STATUSES, true)) {
                    $query->where('status', $status);
                }
            })
            ->when($filters['category_id'] ?? null, function (Builder $query, mixed $categoryId): void {
                $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->where('categories.id', (int) $categoryId));
            })
            ->latest('wp_posts.created_at');
    }

    private function validCategoryIds(array $ids): array
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return [];
        }

        $validIds = Category::query()
            ->where('type', 'post')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if (count($validIds) !== count($ids)) {
            throw ValidationException::withMessages([
                'selectedCategories' => 'Danh muc bai viet khong hop le.',
            ]);
        }

        return $validIds;
    }

    private function categoryIdsFromNames(string|array|null $categories): array
    {
        $names = is_array($categories) ? $categories : explode(',', (string) $categories);

        return collect($names)
            ->map(fn ($name) => trim((string) $name))
            ->filter()
            ->unique(fn ($name) => Str::lower($name))
            ->map(function (string $name): int {
                return (int) Category::query()->firstOrCreate(
                    ['name' => $name, 'type' => 'post'],
                    ['slug' => $this->uniqueCategorySlug(Str::slug($name))]
                )->id;
            })
            ->all();
    }

    private function tagIds(array $tagNames): array
    {
        return collect($tagNames)
            ->map(function (string $name): int {
                return (int) Tag::query()->firstOrCreate(
                    ['name' => $name],
                    ['slug' => $this->uniqueTagSlug(Str::slug($name))]
                )->id;
            })
            ->all();
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->map(fn ($id) => filter_var($id, FILTER_VALIDATE_INT))
            ->filter(fn ($id) => $id !== false && $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function uniqueSlug(string $slug): string
    {
        $base = $slug ?: 'post';
        $candidate = $base;
        $counter = 2;

        while (Post::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniqueCategorySlug(string $slug): string
    {
        $base = $slug ?: 'category';
        $candidate = $base;
        $counter = 2;

        while (Category::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function uniqueTagSlug(string $slug): string
    {
        $base = $slug ?: 'tag';
        $candidate = $base;
        $counter = 2;

        while (Tag::query()->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $counter;
            $counter++;
        }

        return $candidate;
    }

    private function deleteStoredThumbnail(?string $path): void
    {
        if (! $path || Str::startsWith($path, ['http://', 'https://'])) {
            return;
        }

        Storage::disk('public')->delete($path);
    }
}
