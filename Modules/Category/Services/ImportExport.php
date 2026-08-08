<?php

namespace Modules\Category\Services;

use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Modules\Category\Models\Category;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected array $requiredHeaders = [
        'name',
        'type',
    ];

    protected array $uniqueBy = [
        'type',
        'slug',
    ];

    protected array $headerAliases = [
        'name' => ['name', 'ten', 'ten_danh_muc', 'category_name'],
        'slug' => ['slug', 'duong_dan', 'ma_slug'],
        'url' => ['url', 'duong_dan_url', 'lien_ket', 'link'],
        'icon' => ['icon', 'bieu_tuong'],
        'can' => ['can', 'permission', 'quyen'],
        'type' => ['type', 'loai', 'loai_danh_muc', 'category_type'],
        'type_title' => ['type_title', 'ten_loai', 'category_type_title'],
        'parent_slug' => ['parent_slug', 'slug_cha', 'danh_muc_cha'],
        'description' => ['description', 'mo_ta'],
        'is_active' => ['is_active', 'trang_thai', 'active'],
        'sort_order' => ['sort_order', 'thu_tu', 'sap_xep'],
        'meta_title' => ['meta_title', 'seo_title'],
        'meta_description' => ['meta_description', 'seo_description'],
    ];

    protected string $mode = 'update_or_create';

    private array $currentRow = [];

    public function __construct(private readonly CategoryService $categories)
    {
        $this->rules = [
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
            'url' => ['nullable', 'string', 'max:500'],
            'icon' => ['nullable', 'string', 'max:1000'],
            'can' => ['nullable', 'string', 'max:255'],
            'type' => ['required', 'string', 'exists:category_types,type'],
            'type_title' => ['nullable', 'string', 'max:255'],
            'parent_slug' => [
                'nullable',
                'string',
                'max:255',
                function (string $attribute, mixed $value, Closure $fail): void {
                    $this->validateParentSlug($value, $fail);
                },
            ],
            'description' => ['nullable', 'string'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:4294967295'],
            'meta_title' => ['nullable', 'string', 'max:255'],
            'meta_description' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function import(string $filePath, array $options = []): array
    {
        $this->authorizeAdmin('create_category');

        if (($options['mode'] ?? $this->mode) === 'replace') {
            $this->resetReport();
            $this->addError(
                $this->defaultSheetName,
                null,
                'mode',
                'Replace mode is not approved for Category import.'
            );

            return $this->report(false);
        }

        return parent::import($filePath, $options);
    }

    public function export(array $filters = []): string
    {
        $this->authorizeAdmin('view_category');

        return parent::export($filters);
    }

    public function exportTemplate(): string
    {
        $this->authorizeAdmin('view_category');

        return parent::exportTemplate();
    }

    protected function modelClass(): string
    {
        return Category::class;
    }

    protected function normalizeRow(array $row): array
    {
        $name = $this->cleanString($row['name'] ?? null);
        $parentSlug = $this->cleanString($row['parent_slug'] ?? null);

        $this->currentRow = [
            'name' => $name,
            'slug' => $this->categories->normalizeSlug($this->cleanString($row['slug'] ?? null), (string) $name),
            'url' => $this->cleanString($row['url'] ?? null),
            'icon' => $this->cleanString($row['icon'] ?? null),
            'can' => $this->cleanString($row['can'] ?? null),
            'type' => $this->cleanString($row['type'] ?? null),
            'type_title' => $this->cleanString($row['type_title'] ?? null),
            'parent_slug' => $parentSlug === null ? null : $this->categories->normalizeSlug($parentSlug, $parentSlug),
            'description' => $this->cleanString($row['description'] ?? null),
            'is_active' => $this->cleanBoolean($row['is_active'] ?? true) ?? true,
            'sort_order' => $this->cleanInteger($row['sort_order'] ?? 0) ?? 0,
            'meta_title' => $this->cleanString($row['meta_title'] ?? null),
            'meta_description' => $this->cleanString($row['meta_description'] ?? null),
        ];

        return $this->currentRow;
    }

    protected function beforePersist(array $data, array $row, int $rowNumber, string $sheet): array
    {
        $parentSlug = $data['parent_slug'] ?? null;
        unset($data['parent_slug']);

        $data['parent_id'] = $parentSlug
            ? Category::query()
                ->where('type', $data['type'])
                ->where('slug', $parentSlug)
                ->value('id')
            : null;

        return $data;
    }

    protected function persistRow(array $data, string $mode): Model
    {
        $existing = Category::query()
            ->where('type', $data['type'])
            ->where('slug', $data['slug'])
            ->first();

        if ($mode === 'skip_duplicate' && $existing) {
            $this->skippedRows++;

            return $existing;
        }

        if ($mode === 'create_only' && $existing) {
            throw new \RuntimeException('Category already exists for this type and slug.');
        }

        return $existing
            ? $this->categories->update($existing->id, $data)
            : $this->categories->create($data);
    }

    protected function exportRows(array $filters = []): Collection
    {
        return Category::query()
            ->with(['parent:id,slug', 'typeInfo:type,title'])
            ->select([
                'id',
                'name',
                'slug',
                'url',
                'icon',
                'can',
                'type',
                'type_title',
                'parent_id',
                'description',
                'is_active',
                'sort_order',
                'meta_title',
                'meta_description',
                'created_at',
            ])
            ->when($filters['type'] ?? null, fn ($query, string $type) => $query->where('type', $type))
            ->orderBy('type')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var Category $model */
        return [
            'name' => $model->name,
            'slug' => $model->slug,
            'url' => $model->url,
            'icon' => $model->icon,
            'can' => $model->can,
            'type' => $model->type,
            'type_title' => $model->type_title,
            'parent_slug' => $model->parent?->slug,
            'description' => $model->description,
            'is_active' => (bool) $model->is_active,
            'sort_order' => $model->sort_order,
            'meta_title' => $model->meta_title,
            'meta_description' => $model->meta_description,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Danh muc mau',
            'slug' => 'danh-muc-mau',
            'url' => '/admin/example',
            'icon' => 'circle',
            'can' => 'view_category',
            'type' => 'product',
            'type_title' => 'Danh muc',
            'parent_slug' => null,
            'description' => 'Mo ta danh muc',
            'is_active' => true,
            'sort_order' => 0,
            'meta_title' => 'SEO title',
            'meta_description' => 'SEO description',
        ];
    }

    private function validateParentSlug(mixed $value, Closure $fail): void
    {
        $parentSlug = $this->cleanString($value);

        if ($parentSlug === null) {
            return;
        }

        $parentSlug = $this->categories->normalizeSlug($parentSlug, $parentSlug);

        if (
            $parentSlug === $this->currentRow['slug']
            && $this->currentRow['type'] !== null
        ) {
            $fail('Parent category cannot be the same as the imported category.');

            return;
        }

        if ($this->currentRow['type'] === null) {
            return;
        }

        $exists = Category::query()
            ->where('type', $this->currentRow['type'])
            ->where('slug', $parentSlug)
            ->exists();

        if (! $exists) {
            $fail('Parent category does not exist for this type.');
        }
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
