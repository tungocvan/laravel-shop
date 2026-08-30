<?php

namespace Modules\Category\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Category\Models\CategoryType;
use Throwable;

class CategoryService
{
    private const SORTABLE_COLUMNS = [
        'sort_order',
        'name',
        'created_at',
    ];

    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        return $this->treeForAdmin($filters)['categories'];
    }

    /**
     * Build the Admin category tree while paginating root categories only.
     *
     * @return array{
     *     categories: LengthAwarePaginator,
     *     visibleCategoryIds: array<int>|null,
     *     autoExpandedIds: array<int>
     * }
     */
    public function treeForAdmin(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $type = $filters['type'] ?? null;
        $status = (string) ($filters['status'] ?? '');
        $sortBy = in_array($filters['sortBy'] ?? null, self::SORTABLE_COLUMNS, true)
            ? $filters['sortBy']
            : 'sort_order';
        $sortDirection = ($filters['sortDirection'] ?? 'asc') === 'desc' ? 'desc' : 'asc';
        $perPage = in_array((int) ($filters['perPage'] ?? 10), [10, 25, 50, 100], true)
            ? (int) $filters['perPage']
            : 10;

        $catalog = Category::query()
            ->select([
                'id',
                'name',
                'slug',
                'type',
                'parent_id',
                'is_active',
                'sort_order',
                'created_at',
            ])
            ->when($type, fn ($query) => $query->where('type', $type))
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $filtersActive = $search !== '' || in_array($status, ['active', 'inactive'], true);
        $visibleCategoryIds = null;
        $autoExpandedIds = [];
        $rootIds = $catalog
            ->whereNull('parent_id')
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($filtersActive) {
            $byId = $catalog->keyBy('id');
            $matchedIds = $catalog
                ->filter(function (Category $category) use ($search, $status): bool {
                    $matchesSearch = $search === ''
                        || Str::contains(Str::lower((string) $category->name), Str::lower($search))
                        || Str::contains(Str::lower((string) $category->slug), Str::lower($search));
                    $matchesStatus = $status === ''
                        || ($status === 'active' && $category->is_active)
                        || ($status === 'inactive' && ! $category->is_active);

                    return $matchesSearch && $matchesStatus;
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $visibleLookup = [];
            $expandedLookup = [];
            $rootLookup = [];

            foreach ($matchedIds as $matchedId) {
                $cursor = $matchedId;
                $visited = [];

                while ($cursor !== null && ! isset($visited[$cursor])) {
                    $visited[$cursor] = true;
                    $visibleLookup[$cursor] = true;
                    $category = $byId->get($cursor);

                    if (! $category) {
                        break;
                    }

                    $parentId = $category->parent_id !== null ? (int) $category->parent_id : null;

                    if ($parentId === null) {
                        $rootLookup[(int) $category->id] = true;
                        break;
                    }

                    $expandedLookup[$parentId] = true;
                    $cursor = $parentId;
                }
            }

            $visibleCategoryIds = array_map('intval', array_keys($visibleLookup));
            $autoExpandedIds = array_map('intval', array_keys($expandedLookup));
            $rootIds = array_map('intval', array_keys($rootLookup));
        }

        $categories = Category::query()
            ->select([
                'id',
                'name',
                'slug',
                'type',
                'parent_id',
                'image',
                'is_active',
                'sort_order',
                'created_at',
            ])
            ->whereNull('parent_id')
            ->when($type, fn ($query) => $query->where('type', $type))
            ->when($filtersActive, fn ($query) => $query->whereIn('id', $rootIds ?: [-1]))
            ->with([
                'typeInfo:type,title,icon',
                'childrenRecursive',
            ])
            ->orderBy($sortBy, $sortDirection)
            ->orderBy('id')
            ->paginate($perPage);

        return [
            'categories' => $categories,
            'visibleCategoryIds' => $visibleCategoryIds,
            'autoExpandedIds' => $autoExpandedIds,
        ];
    }

    public function findForEdit(int $id): Category
    {
        return Category::query()->findOrFail($id);
    }

    public function parentOptions(string $type, ?int $excludeId = null): array
    {
        $categories = Category::query()
            ->where('type', $type)
            ->select(['id', 'name', 'parent_id'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        $excludedIds = $excludeId === null
            ? []
            : $this->descendantIds($categories, $excludeId);

        if ($excludeId !== null) {
            $excludedIds[] = $excludeId;
        }

        $excludedLookup = array_fill_keys($excludedIds, true);
        $childrenByParent = $categories
            ->reject(fn (Category $category) => isset($excludedLookup[$category->id]))
            ->groupBy(fn (Category $category) => $category->parent_id ?? 0);

        $options = [];
        $visited = [];

        $appendChildren = function (int $parentId, string $prefix = '') use (
            &$appendChildren,
            &$options,
            &$visited,
            $childrenByParent
        ): void {
            foreach ($childrenByParent->get($parentId, collect()) as $category) {
                if (isset($visited[$category->id])) {
                    continue;
                }

                $visited[$category->id] = true;
                $options[] = [
                    'id' => $category->id,
                    'label' => $prefix.$category->name,
                ];

                $appendChildren($category->id, $prefix.'-- ');
            }
        };

        $appendChildren(0);

        foreach ($categories as $category) {
            if (! isset($excludedLookup[$category->id]) && ! isset($visited[$category->id])) {
                $appendChildren($category->parent_id ?? 0);
            }
        }

        return $options;
    }

    public function create(array $data): Category
    {
        $normalized = $this->normalizeData($data);
        $this->validateInvariants($normalized);
        $newImagePath = $this->storeImage($data['newImage'] ?? null);

        try {
            return DB::transaction(function () use ($normalized, $newImagePath) {
                if ($newImagePath !== null) {
                    $normalized['image'] = $newImagePath;
                }

                return Category::query()->create($normalized);
            });
        } catch (Throwable $exception) {
            $this->deleteImage($newImagePath);

            throw $exception;
        }
    }

    public function update(int $id, array $data): Category
    {
        $current = $this->findForEdit($id);
        $normalized = $this->normalizeData($data);
        $this->validateInvariants($normalized, $id);
        $newImagePath = $this->storeImage($data['newImage'] ?? null);
        $oldImagePath = $current->image;

        try {
            $category = DB::transaction(function () use ($id, $normalized, $newImagePath) {
                $category = Category::query()->lockForUpdate()->findOrFail($id);

                if ($newImagePath !== null) {
                    $normalized['image'] = $newImagePath;
                }

                $category->update($normalized);

                return $category->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteImage($newImagePath);

            throw $exception;
        }

        if ($newImagePath !== null && $oldImagePath !== $newImagePath) {
            $this->deleteImage($oldImagePath);
        }

        return $category;
    }

    public function delete(int $id): void
    {
        $imagePath = DB::transaction(function () use ($id) {
            $category = Category::query()->lockForUpdate()->findOrFail($id);

            if ($category->children()->exists()) {
                throw ValidationException::withMessages([
                    'delete' => 'Không thể xóa danh mục đang có danh mục con.',
                ]);
            }

            $imagePath = $category->image;
            $category->delete();

            return $imagePath;
        });

        $this->deleteImage($imagePath);
    }

    public function setActive(int $id, bool $active): Category
    {
        return DB::transaction(function () use ($id, $active) {
            $category = Category::query()->lockForUpdate()->findOrFail($id);
            $category->update(['is_active' => $active]);

            return $category->refresh();
        });
    }

    public function normalizeSlug(?string $slug, string $name): ?string
    {
        $normalized = Str::slug(trim((string) $slug) ?: $name);

        return $normalized !== '' ? $normalized : null;
    }

    private function normalizeData(array $data): array
    {
        return [
            'name' => trim((string) $data['name']),
            'slug' => $this->normalizeSlug($data['slug'] ?? null, (string) $data['name']),
            'url' => $this->nullableString($data['url'] ?? null),
            'icon' => $this->nullableString($data['icon'] ?? null),
            'can' => $this->nullableString($data['can'] ?? null),
            'type' => (string) $data['type'],
            'type_title' => $this->nullableString($data['type_title'] ?? null),
            'parent_id' => $data['parent_id'] ?: null,
            'description' => $this->nullableString($data['description'] ?? null),
            'sort_order' => (int) ($data['sort_order'] ?? 0),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'meta_title' => $this->nullableString($data['meta_title'] ?? null),
            'meta_description' => $this->nullableString($data['meta_description'] ?? null),
        ];
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function validateInvariants(array $data, ?int $categoryId = null): void
    {
        if (! CategoryType::query()->whereKey($data['type'])->exists()) {
            throw ValidationException::withMessages([
                'type' => 'Loại danh mục không tồn tại.',
            ]);
        }

        if (
            $data['slug'] !== null
            && Category::query()
                ->where('slug', $data['slug'])
                ->when($categoryId, fn ($query) => $query->where('id', '!=', $categoryId))
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'slug' => 'Slug đã tồn tại.',
            ]);
        }

        if ($data['parent_id'] === null) {
            return;
        }

        $parent = Category::query()->find($data['parent_id']);

        if (! $parent) {
            throw ValidationException::withMessages([
                'parent_id' => 'Danh mục cha không tồn tại.',
            ]);
        }

        if ($parent->type !== $data['type']) {
            throw ValidationException::withMessages([
                'parent_id' => 'Danh mục cha phải cùng loại.',
            ]);
        }

        if ($categoryId === null) {
            return;
        }

        $parentMap = Category::query()
            ->where('type', $data['type'])
            ->pluck('parent_id', 'id')
            ->all();
        $cursor = $data['parent_id'];
        $visited = [];

        while ($cursor !== null) {
            if ((int) $cursor === $categoryId) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Không thể chọn danh mục con làm danh mục cha.',
                ]);
            }

            if (isset($visited[$cursor])) {
                throw ValidationException::withMessages([
                    'parent_id' => 'Cây danh mục hiện có chu kỳ dữ liệu.',
                ]);
            }

            $visited[$cursor] = true;
            $cursor = $parentMap[$cursor] ?? null;
        }
    }

    private function descendantIds(Collection $categories, int $categoryId): array
    {
        $childrenByParent = $categories->groupBy('parent_id');
        $descendants = [];
        $queue = [$categoryId];
        $visited = [];

        while ($queue !== []) {
            $parentId = array_shift($queue);

            if (isset($visited[$parentId])) {
                continue;
            }

            $visited[$parentId] = true;

            foreach ($childrenByParent->get($parentId, collect()) as $child) {
                $descendants[] = $child->id;
                $queue[] = $child->id;
            }
        }

        return $descendants;
    }

    private function storeImage(mixed $image): ?string
    {
        if ($image === null) {
            return null;
        }

        return $image->store('categories', 'public');
    }

    private function deleteImage(?string $path): void
    {
        if (! $this->isOwnedImagePath($path)) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function isOwnedImagePath(?string $path): bool
    {
        return is_string($path)
            && Str::startsWith($path, 'categories/')
            && ! Str::contains($path, ['..', '\\']);
    }
}
