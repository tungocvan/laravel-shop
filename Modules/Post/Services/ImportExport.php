<?php

namespace Modules\Post\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Modules\Post\Models\Post;
use Modules\Shared\Services\ImportExport\BaseImportExportService;

class ImportExport extends BaseImportExportService
{
    protected array $requiredHeaders = ['name'];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'slug' => ['nullable', 'string', 'max:255'],
        'summary' => ['nullable', 'string'],
        'content' => ['nullable', 'string'],
        'status' => ['nullable', 'in:published,draft,hidden'],
        'is_featured' => ['nullable'],
        'thumbnail' => ['nullable', 'string', 'max:255'],
        'meta_title' => ['nullable', 'string', 'max:255'],
        'meta_description' => ['nullable', 'string', 'max:255'],
        'categories' => ['nullable', 'string'],
        'tags' => ['nullable', 'string'],
    ];

    protected array $uniqueBy = ['slug'];

    protected string $mode = 'update_or_create';

    public function __construct(private readonly PostService $posts)
    {
    }

    public function import(string $filePath, array $options = []): array
    {
        $this->authorizeAdmin('create_post');

        return parent::import($filePath, $options);
    }

    public function export(array $filters = []): string
    {
        $this->authorizeAdmin('view_post');

        return parent::export($filters);
    }

    public function exportTemplate(): string
    {
        $this->authorizeAdmin('view_post');

        return parent::exportTemplate();
    }

    protected function modelClass(): string
    {
        return Post::class;
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'name' => trim((string) ($row['name'] ?? '')),
            'slug' => Str::slug($row['slug'] ?? $row['name'] ?? ''),
            'summary' => $this->nullableString($row['summary'] ?? null),
            'content' => $this->nullableString($row['content'] ?? null),
            'status' => $row['status'] ?: 'draft',
            'is_featured' => $this->normalizeBoolean($row['is_featured'] ?? false),
            'thumbnail' => $this->nullableString($row['thumbnail'] ?? null),
            'meta_title' => $this->nullableString($row['meta_title'] ?? null),
            'meta_description' => $this->nullableString($row['meta_description'] ?? null),
            'categories' => $this->nullableString($row['categories'] ?? null),
            'tags' => $this->nullableString($row['tags'] ?? null),
            'user_id' => auth('admin')->id(),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return $this->posts->exportRows(
            $filters,
            isset($filters['ids']) && is_array($filters['ids']) ? $filters['ids'] : null
        );
    }

    protected function persistRow(array $data, string $mode): Model
    {
        return $this->posts->importRow($data, $mode);
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var Post $model */
        return [
            'name' => $model->name,
            'slug' => $model->slug,
            'summary' => $model->summary,
            'content' => $model->content,
            'status' => $model->status,
            'is_featured' => (bool) $model->is_featured,
            'thumbnail' => $model->thumbnail,
            'categories' => $model->categories->pluck('name')->implode(', '),
            'tags' => $model->tags->pluck('name')->implode(', '),
            'meta_title' => $model->meta_title,
            'meta_description' => $model->meta_description,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Tieu de bai viet mau',
            'slug' => 'tieu-de-bai-viet-mau',
            'summary' => 'Tom tat ngan',
            'content' => 'Noi dung bai viet',
            'status' => 'draft',
            'is_featured' => false,
            'thumbnail' => null,
            'categories' => 'Tin tuc, Huong dan',
            'tags' => 'laravel, livewire',
            'meta_title' => 'Tieu de SEO',
            'meta_description' => 'Mo ta SEO',
        ];
    }

    private function normalizeBoolean(mixed $value): bool
    {
        if (is_bool($value)) {
            return $value;
        }

        return in_array(Str::lower((string) $value), ['1', 'true', 'yes', 'y', 'co', 'có'], true);
    }

    private function nullableString(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
