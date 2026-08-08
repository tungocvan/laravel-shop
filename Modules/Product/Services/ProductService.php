<?php

namespace Modules\Product\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Category\Models\Category;
use Modules\Product\Models\Product;
use Throwable;

class ProductService
{
    private const SORTABLE_COLUMNS = [
        'title',
        'regular_price',
        'quantity',
        'is_active',
        'created_at',
    ];

    private const PER_PAGE_OPTIONS = [10, 25, 50, 100];

    public function paginateForAdmin(array $filters): LengthAwarePaginator
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $categoryId = $filters['category_id'] ?? null;
        $sortColumn = $this->normalizeSortColumn($filters['sortColumn'] ?? null);
        $sortDirection = ($filters['sortDirection'] ?? 'desc') === 'asc' ? 'asc' : 'desc';
        $perPage = $this->normalizePerPage($filters['perPage'] ?? 10);

        return $this->baseAdminQuery($search, $categoryId)
            ->orderBy($sortColumn, $sortDirection)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function currentPageIds(array $filters): array
    {
        return $this->paginateForAdmin($filters)
            ->getCollection()
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    public function findForEdit(int $id): Product
    {
        return Product::query()
            ->with('categories:id,name')
            ->findOrFail($id);
    }

    public function productCategoryTree(): EloquentCollection
    {
        return Category::query()
            ->where('type', 'product')
            ->whereNull('parent_id')
            ->with(['children.children'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    public function flatProductCategoryOptions(): Collection
    {
        $categories = Category::query()
            ->where('type', 'product')
            ->select(['id', 'name', 'parent_id', 'sort_order'])
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();

        return collect($this->buildFlatCategoryOptions($categories));
    }

    public function create(array $data): Product
    {
        return $this->saveProduct(null, $data);
    }

    public function update(int $id, array $data): Product
    {
        return $this->saveProduct($id, $data);
    }

    public function duplicate(int $id): Product
    {
        return DB::transaction(function () use ($id) {
            $product = Product::query()
                ->with('categories:id')
                ->lockForUpdate()
                ->findOrFail($id);

            $duplicate = $product->replicate();
            $duplicate->title = $product->title.' (Copy)';
            $duplicate->slug = $this->uniqueSlug(Str::slug($duplicate->title));
            $duplicate->is_active = false;
            $duplicate->created_at = now();
            $duplicate->updated_at = now();
            $duplicate->save();

            $duplicate->categories()->sync($product->categories->pluck('id')->all());

            return $duplicate->refresh();
        });
    }

    public function toggleStatus(int $id): Product
    {
        return DB::transaction(function () use ($id) {
            $product = Product::query()->lockForUpdate()->findOrFail($id);
            $product->update(['is_active' => ! $product->is_active]);

            return $product->refresh();
        });
    }

    public function delete(int $id): void
    {
        Product::query()->whereKey($id)->delete();
    }

    public function deleteMany(array $ids): void
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return;
        }

        Product::query()->whereIn('id', $ids)->delete();
    }

    public function addCategoriesToProducts(array $productIds, array $categoryIds): void
    {
        $productIds = $this->normalizeIds($productIds);
        $categoryIds = $this->normalizeCategoryIds($categoryIds);

        if ($productIds === [] || $categoryIds === []) {
            return;
        }

        DB::transaction(function () use ($productIds, $categoryIds) {
            Product::query()
                ->whereIn('id', $productIds)
                ->get()
                ->each(fn (Product $product) => $product->categories()->syncWithoutDetaching($categoryIds));
        });
    }

    public function removeCategory(int $productId, int $categoryId): void
    {
        $this->normalizeCategoryIds([$categoryId]);

        Product::query()
            ->findOrFail($productId)
            ->categories()
            ->detach($categoryId);
    }

    public function importRow(array $data): Product
    {
        $categoryIds = $this->parseCategoryIds($data['category_ids'] ?? null);

        return $this->create([
            'title' => $data['title'] ?? null,
            'slug' => $data['slug'] ?? null,
            'regular_price' => $data['regular_price'] ?? 0,
            'sale_price' => $data['sale_price'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'description' => $data['description'] ?? null,
            'image' => $data['image'] ?? null,
            'gallery' => $data['gallery'] ?? [],
            'tags' => $data['tags'] ?? [],
            'is_active' => $data['is_active'] ?? true,
            'category_ids' => $categoryIds,
        ]);
    }

    public function normalizeSlug(?string $slug, string $title): string
    {
        $normalized = Str::slug(trim((string) $slug) ?: $title);

        if ($normalized === '') {
            throw ValidationException::withMessages([
                'slug' => 'Slug không hợp lệ.',
            ]);
        }

        return $normalized;
    }

    public function normalizeSortColumn(?string $column): string
    {
        return in_array($column, self::SORTABLE_COLUMNS, true) ? $column : 'created_at';
    }

    public function normalizePerPage(int|string|null $perPage): int
    {
        if ($perPage === 'all') {
            return 100;
        }

        $perPage = (int) ($perPage ?? 10);

        return in_array($perPage, self::PER_PAGE_OPTIONS, true) ? $perPage : 10;
    }

    public function exportRows(?array $ids = null, int $limit = 5000): EloquentCollection
    {
        $query = Product::query()
            ->with('categories:id,name')
            ->orderBy('id');

        if ($ids !== null && $ids !== []) {
            $query->whereIn('id', $this->normalizeIds($ids));
        } else {
            $query->limit($limit);
        }

        return $query->get();
    }

    private function saveProduct(?int $id, array $data): Product
    {
        $normalized = $this->normalizeProductData($data, $id);
        $categoryIds = $this->normalizeCategoryIds($data['category_ids'] ?? []);
        $newImagePath = $this->storeImage($data['newImage'] ?? null, 'products');
        $newGalleryPaths = $this->storeGallery($data['newGallery'] ?? []);
        $oldImagePath = null;

        try {
            $product = DB::transaction(function () use ($id, $normalized, $categoryIds, $newImagePath, $newGalleryPaths, &$oldImagePath) {
                if ($newImagePath !== null) {
                    $normalized['image'] = $newImagePath;
                }

                $normalized['gallery'] = array_values(array_merge($normalized['gallery'] ?? [], $newGalleryPaths));

                if ($id !== null) {
                    $product = Product::query()->lockForUpdate()->findOrFail($id);
                    $oldImagePath = $product->image;
                    $product->update($normalized);
                } else {
                    $product = Product::query()->create($normalized);
                }

                $product->categories()->sync($categoryIds);

                return $product->refresh();
            });
        } catch (Throwable $exception) {
            $this->deleteOwnedFiles(array_merge([$newImagePath], $newGalleryPaths));

            throw $exception;
        }

        if ($newImagePath !== null && $oldImagePath !== $newImagePath) {
            $this->deleteOwnedFiles([$oldImagePath]);
        }

        return $product;
    }

    private function normalizeProductData(array $data, ?int $id = null): array
    {
        $title = trim((string) ($data['title'] ?? ''));
        $slug = $this->normalizeSlug($data['slug'] ?? null, $title);
        $regularPrice = $this->normalizeNullableNumber($data['regular_price'] ?? null, 'regular_price') ?? 0;
        $salePrice = $this->normalizeNullableNumber($data['sale_price'] ?? null, 'sale_price');
        $commissionRate = $this->normalizeNullableNumber($data['affiliate_commission_rate'] ?? null, 'affiliate_commission_rate');

        if ($title === '') {
            throw ValidationException::withMessages([
                'title' => 'Tên sản phẩm là bắt buộc.',
            ]);
        }

        if ($salePrice !== null && $salePrice > $regularPrice) {
            throw ValidationException::withMessages([
                'sale_price' => 'Giá khuyến mãi không được lớn hơn giá bán thường.',
            ]);
        }

        if ($commissionRate !== null && ($commissionRate < 0 || $commissionRate > 100)) {
            throw ValidationException::withMessages([
                'affiliate_commission_rate' => 'Tỷ lệ hoa hồng phải từ 0 đến 100.',
            ]);
        }

        if (
            Product::query()
                ->where('slug', $slug)
                ->when($id, fn (Builder $query) => $query->where('id', '!=', $id))
                ->exists()
        ) {
            throw ValidationException::withMessages([
                'slug' => 'Slug đã tồn tại.',
            ]);
        }

        $normalized = [
            'title' => $title,
            'slug' => $slug,
            'regular_price' => $regularPrice,
            'sale_price' => $salePrice,
            'short_description' => $this->normalizeNullableString($data['short_description'] ?? null),
            'description' => $this->normalizeNullableString($data['description'] ?? null),
            'is_active' => (bool) ($data['is_active'] ?? false),
            'tags' => $this->normalizeStringArray($data['tags'] ?? []),
            'gallery' => $this->normalizePathArray($data['gallery'] ?? []),
            'affiliate_commission_rate' => $commissionRate,
        ];

        if (array_key_exists('image', $data)) {
            $normalized['image'] = $this->normalizeImagePath($data['image']);
        }

        return $normalized;
    }

    private function baseAdminQuery(string $search, mixed $categoryId): Builder
    {
        return Product::query()
            ->with('categories:id,name')
            ->when($search !== '', function (Builder $query) use ($search) {
                $query->where(function (Builder $nested) use ($search) {
                    $nested
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->when($categoryId !== null && $categoryId !== '', function (Builder $query) use ($categoryId) {
                $query->whereHas('categories', fn (Builder $categoryQuery) => $categoryQuery->whereKey((int) $categoryId));
            });
    }

    private function normalizeCategoryIds(array $ids): array
    {
        $ids = $this->normalizeIds($ids);

        if ($ids === []) {
            return [];
        }

        $existing = Category::query()
            ->where('type', 'product')
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        sort($ids);
        sort($existing);

        if ($ids !== $existing) {
            throw ValidationException::withMessages([
                'category_ids' => 'Danh mục sản phẩm không hợp lệ.',
            ]);
        }

        return $ids;
    }

    private function normalizeIds(array $ids): array
    {
        return collect($ids)
            ->filter(fn ($id) => is_numeric($id))
            ->map(fn ($id) => (int) $id)
            ->filter(fn (int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function parseCategoryIds(mixed $value): array
    {
        if (is_array($value)) {
            return $value;
        }

        return collect(explode(',', (string) $value))
            ->map(fn ($id) => trim($id))
            ->all();
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value !== '' ? $value : null;
    }

    private function normalizeNullableNumber(mixed $value, string $field): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            throw ValidationException::withMessages([
                $field => 'Giá trị phải là số.',
            ]);
        }

        $value = (float) $value;

        if ($value < 0) {
            throw ValidationException::withMessages([
                $field => 'Giá trị không được âm.',
            ]);
        }

        return $value;
    }

    private function normalizeStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->map(fn ($item) => trim((string) $item))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function normalizePathArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        return collect($value)
            ->filter(fn ($path) => is_string($path) && ! Str::contains($path, ['..', '\\']))
            ->values()
            ->all();
    }

    private function normalizeImagePath(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        if ($value === '' || Str::contains($value, ['..', '\\'])) {
            return null;
        }

        return $value;
    }

    private function storeImage(mixed $image, string $directory): ?string
    {
        if ($image === null) {
            return null;
        }

        return $image->store($directory, 'public');
    }

    private function storeGallery(mixed $gallery): array
    {
        if (! is_array($gallery)) {
            return [];
        }

        return collect($gallery)
            ->map(fn ($file) => $this->storeImage($file, 'products/gallery'))
            ->filter()
            ->values()
            ->all();
    }

    private function deleteOwnedFiles(array $paths): void
    {
        foreach ($paths as $path) {
            if ($this->isOwnedProductPath($path)) {
                Storage::disk('public')->delete($path);
            }
        }
    }

    private function isOwnedProductPath(?string $path): bool
    {
        return is_string($path)
            && Str::startsWith($path, 'products/')
            && ! Str::contains($path, ['..', '\\']);
    }

    private function uniqueSlug(string $baseSlug): string
    {
        $baseSlug = $baseSlug !== '' ? $baseSlug : 'product';
        $slug = $baseSlug;
        $suffix = 1;

        while (Product::query()->where('slug', $slug)->exists()) {
            $slug = $baseSlug.'-'.$suffix;
            $suffix++;
        }

        return $slug;
    }

    private function buildFlatCategoryOptions(Collection $categories, ?int $parentId = null, string $prefix = ''): array
    {
        $result = [];

        foreach ($categories as $category) {
            if ((int) ($category->parent_id ?? 0) !== (int) ($parentId ?? 0)) {
                continue;
            }

            $category->view_name = $prefix.$category->name;
            $result[] = $category;
            $result = array_merge(
                $result,
                $this->buildFlatCategoryOptions($categories, (int) $category->id, $prefix.'-- ')
            );
        }

        return $result;
    }
}
