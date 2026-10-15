<?php

namespace Modules\Category\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Rap2hpoutre\FastExcel\FastExcel;
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
            'parent_slug' => ['nullable', 'string', 'max:255'],
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
        $this->resetReport();

        $mode = $options['mode'] ?? $this->mode;
        $dryRun = (bool) ($options['dry_run'] ?? false);
        if (! in_array($mode, ['create_only', 'update_or_create', 'skip_duplicate'], true)) {
            $this->addError($this->defaultSheetName, null, 'mode', 'Chế độ import không được hỗ trợ.');

            return $this->report(false);
        }

        try {
            $this->validateImportFile($filePath);
            $reader = new FastExcel;
            if (strtolower(pathinfo($filePath, PATHINFO_EXTENSION)) === 'csv') {
                $reader->configureCsv($this->csvDelimiter());
            }
            $pending = [];
            foreach ($reader->import($filePath) as $index => $rawRow) {
                $this->totalRows++;
                $rowNumber = $index + 2;
                $row = $this->normalizeRowHeaders((array) $rawRow);
                if (! $this->hasRequiredHeaders($row)) {
                    $this->addError($this->defaultSheetName, $rowNumber, null, 'File thiếu cột bắt buộc.');
                    continue;
                }
                $pending[] = ['number' => $rowNumber, 'data' => $this->normalizeRow($row)];
            }

            if ($this->errors !== []) {
                return $this->report(false);
            }

            DB::beginTransaction();
            try {
                // Parent rows may appear after their children. Persist in dependency
                // order, in one transaction. Dry-run follows the same real validation.
                while ($pending !== []) {
                    $next = [];
                    $progress = false;
                    foreach ($pending as $item) {
                        $data = $item['data'];
                        $parentSlug = $data['parent_slug'] ?? null;
                        if ($parentSlug !== null && $parentSlug === $data['slug']) {
                            $this->addError($this->defaultSheetName, $item['number'], 'parent_slug', 'Parent category cannot be the same as the imported category.', $parentSlug);
                            continue;
                        }
                        if ($parentSlug !== null && ! Category::query()
                            ->where('type', $data['type'])
                            ->where('slug', $parentSlug)->exists()) {
                            $next[] = $item;
                            continue;
                        }

                        $validator = Validator::make($data, $this->rules);
                        if ($validator->fails()) {
                            foreach ($validator->errors()->messages() as $column => $messages) {
                                foreach ($messages as $message) {
                                    $this->addError($this->defaultSheetName, $item['number'], $column, $message, $data[$column] ?? null);
                                }
                            }
                            continue;
                        }

                        try {
                            $prepared = $this->beforePersist($data, $data, $item['number'], $this->defaultSheetName);
                            $skipped = $this->skippedRows;
                            $this->persistRow($prepared, $mode);
                            if ($skipped === $this->skippedRows) {
                                $this->successRows++;
                            }
                            $progress = true;
                        } catch (\Throwable $exception) {
                            \Illuminate\Support\Facades\Log::warning('Category import row failed', [
                                'row' => $item['number'], 'message' => $exception->getMessage(),
                            ]);
                            $this->addError($this->defaultSheetName, $item['number'], null, 'Không thể xử lý dòng dữ liệu này.');
                        }
                    }

                    if (! $progress) {
                        foreach ($next as $item) {
                            $this->addError(
                                $this->defaultSheetName, $item['number'], 'parent_slug',
                                'Không tìm thấy danh mục cha cùng loại hoặc cây danh mục có chu kỳ.',
                                $item['data']['parent_slug']
                            );
                        }
                        break;
                    }
                    $pending = $next;
                }

                if ($dryRun || $this->errors !== []) {
                    DB::rollBack();
                    if ($this->errors !== []) {
                        $this->successRows = 0;
                        $this->skippedRows = 0;
                    }
                } else {
                    DB::commit();
                }
            } catch (\Throwable $exception) {
                DB::rollBack();
                throw $exception;
            }

            return $this->report($this->errors === []);
        } catch (\Throwable $exception) {
            \Illuminate\Support\Facades\Log::error('Category import failed', ['message' => $exception->getMessage()]);
            $this->addError($this->defaultSheetName, null, null, 'Lỗi hệ thống khi import. Vui lòng kiểm tra log.');

            return $this->report(false);
        }
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

        return [
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

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
