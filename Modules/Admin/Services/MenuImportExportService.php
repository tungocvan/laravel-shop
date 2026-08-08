<?php

namespace Modules\Admin\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as BaseCollection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;
use Rap2hpoutre\FastExcel\FastExcel;

class MenuImportExportService
{
    private string $sheet = 'menus';

    private array $headers = [
        'key',
        'parent_key',
        'name',
        'url',
        'icon',
        'can',
        'is_active',
        'sort_order',
    ];

    public function defaultPath(): string
    {
        return base_path('Modules/Admin/data/menus.json');
    }

    public function export(array $filters = []): string
    {
        $rows = $this->flattenMenus($filters);

        if ($rows->isEmpty()) {
            throw new \RuntimeException('Khong co du lieu menu de export.');
        }

        return $this->writeSpreadsheet($rows, 'menus');
    }

    public function exportTemplate(): string
    {
        return $this->writeSpreadsheet(collect([
            [
                'key' => 'dashboard',
                'parent_key' => null,
                'name' => 'Dashboard',
                'url' => '/admin',
                'icon' => 'home',
                'can' => 'admin.dashboard.view',
                'is_active' => 1,
                'sort_order' => 0,
            ],
            [
                'key' => 'system.database',
                'parent_key' => 'system',
                'name' => 'Database',
                'url' => '/admin/system/database',
                'icon' => 'database',
                'can' => 'admin.database.view',
                'is_active' => 0,
                'sort_order' => 1,
            ],
        ]), 'menus-template');
    }

    public function importFromFile(string $filePath, array $options = []): array
    {
        $this->validateReadableFile($filePath);

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if ($extension === 'json') {
            return $this->importFromJson(File::get($filePath), $options);
        }

        return $this->importFromSpreadsheet($filePath, $options);
    }

    public function restoreDefaults(): array
    {
        return $this->importFromJson(File::get($this->defaultPath()), [
            'mode' => 'replace',
            'source' => 'default_json',
        ]);
    }

    public function importFromSpreadsheet(string $filePath, array $options = []): array
    {
        $mode = $options['mode'] ?? 'skip_duplicate';
        $report = $this->blankReport($mode, $options['source'] ?? 'excel_upload', 'excel_flat');

        try {
            if (! in_array($mode, ['update_or_create', 'skip_duplicate', 'replace'], true)) {
                throw new \InvalidArgumentException('Che do import menu khong hop le.');
            }

            $rows = (new FastExcel())->import($filePath)
                ->map(fn ($row): array => $this->normalizeExcelRow((array) $row))
                ->filter(fn (array $row): bool => $this->rowHasData($row))
                ->values();

            $report['total_rows'] = $rows->count();
            $report['debug']['headers'] = $this->headers;

            $this->validateExcelRows($rows, $report);

            if ($report['error_rows'] > 0) {
                return $report;
            }

            DB::transaction(function () use ($rows, $mode, &$report): void {
                if ($mode === 'replace') {
                    AdminMenu::menu()->delete();
                }

                $menusByKey = [];
                $persistedKeys = [];
                $seenKeys = [];

                foreach ($rows as $index => $row) {
                    $key = $row['key'];

                    if (in_array($key, $seenKeys, true)) {
                        $report['skipped_rows']++;
                        continue;
                    }

                    $seenKeys[] = $key;

                    $existing = AdminMenu::query()
                        ->where('slug', $key)
                        ->first();

                    if ($existing && $mode === 'skip_duplicate') {
                        $menusByKey[$key] = $existing;
                        $report['skipped_rows']++;
                        continue;
                    }

                    $menu = AdminMenu::query()->updateOrCreate(
                        ['slug' => $key],
                        [
                            'name' => $row['name'],
                            'url' => $row['url'],
                            'icon' => $row['icon'],
                            'can' => $row['can'],
                            'is_active' => $row['is_active'],
                            'sort_order' => $row['sort_order'] ?? $index,
                            'parent_id' => null,
                        ]
                    );

                    $menusByKey[$key] = $menu;
                    $persistedKeys[] = $key;
                    $report['success_rows']++;
                }

                foreach ($rows as $row) {
                    if (! in_array($row['key'], $persistedKeys, true)) {
                        continue;
                    }

                    $menu = $menusByKey[$row['key']] ?? null;

                    if (! $menu) {
                        continue;
                    }

                    $parentKey = $row['parent_key'];
                    $parent = $parentKey
                        ? ($menusByKey[$parentKey] ?? AdminMenu::query()->where('slug', $parentKey)->first())
                        : null;

                    $menu->update([
                        'parent_id' => $parent?->getKey(),
                        'sort_order' => $row['sort_order'] ?? $menu->sort_order,
                    ]);
                }
            });

            AdminMenu::clearMenuCache();
            $report['success'] = $report['error_rows'] === 0;

            return $report;
        } catch (\Throwable $exception) {
            Log::error('Menu Excel import failed', [
                'service' => static::class,
                'file' => $filePath,
                'mode' => $mode,
                'message' => $exception->getMessage(),
            ]);

            $this->addError($report, null, null, null, 'Loi he thong khi import menu Excel. Vui long kiem tra log.');
            $report['debug']['exception'] = $exception->getMessage();

            return $report;
        }
    }

    public function importFromJson(string $content, array $options = []): array
    {
        $mode = $options['mode'] ?? 'replace';
        $source = $options['source'] ?? 'json';
        $report = $this->blankReport($mode, $source, 'json_tree');

        try {
            if (! in_array($mode, ['skip_duplicate', 'update_or_create', 'replace'], true)) {
                throw new \InvalidArgumentException('Che do import menu khong hop le.');
            }

            $items = json_decode($content, true);

            if (json_last_error() !== JSON_ERROR_NONE || ! is_array($items)) {
                throw new \InvalidArgumentException('File JSON menu khong hop le.');
            }

            $this->validateTree($items, $report);

            if ($report['error_rows'] > 0) {
                return $report;
            }

            DB::transaction(function () use ($items, $mode, &$report): void {
                if ($mode === 'replace') {
                    AdminMenu::menu()->delete();
                }

                foreach ($items as $index => $item) {
                    $this->persistJsonNode($item, null, $index, $mode, $report);
                }
            });

            AdminMenu::clearMenuCache();
            $report['success'] = $report['error_rows'] === 0;

            return $report;
        } catch (\InvalidArgumentException $exception) {
            $this->addError($report, null, null, null, $exception->getMessage());
            $report['debug']['exception'] = $exception->getMessage();

            return $report;
        } catch (\Throwable $exception) {
            Log::error('Menu JSON import failed', [
                'service' => static::class,
                'source' => $source,
                'mode' => $mode,
                'message' => $exception->getMessage(),
            ]);

            $this->addError($report, null, null, null, 'Loi he thong khi import menu JSON. Vui long kiem tra log.');
            $report['debug']['exception'] = $exception->getMessage();

            return $report;
        }
    }

    private function flattenMenus(array $filters): BaseCollection
    {
        $roots = $this->query($filters)
            ->with('children')
            ->whereNull('parent_id')
            ->get();

        return collect($this->flattenMenuCollection($roots));
    }

    private function flattenMenuCollection(Collection $menus, ?string $parentKey = null): array
    {
        $rows = [];

        foreach ($menus as $menu) {
            $key = $this->menuKey($menu);

            $rows[] = [
                'key' => $key,
                'parent_key' => $parentKey,
                'name' => $menu->name,
                'url' => $menu->url,
                'icon' => $menu->icon,
                'can' => $menu->can,
                'is_active' => $menu->is_active ? 1 : 0,
                'sort_order' => $menu->sort_order,
            ];

            if ($menu->children->isNotEmpty()) {
                $rows = array_merge($rows, $this->flattenMenuCollection($menu->children, $key));
            }
        }

        return $rows;
    }

    private function writeSpreadsheet(BaseCollection $rows, string $name): string
    {
        $directory = storage_path('app/public/exports');
        File::ensureDirectoryExists($directory);

        $path = 'exports/' . $name . '-' . now()->format('Ymd-His') . '.xlsx';

        (new FastExcel($rows))->export(storage_path('app/public/' . $path));

        return $path;
    }

    private function normalizeExcelRow(array $raw): array
    {
        $row = [];

        foreach ($raw as $header => $value) {
            $row[$this->normalizeHeader((string) $header)] = $value;
        }

        return [
            'key' => $this->normalizeKey($row['key'] ?? null),
            'parent_key' => $this->normalizeKey($row['parent_key'] ?? null),
            'name' => $this->nullableString($row['name'] ?? null),
            'url' => $this->nullableString($row['url'] ?? null),
            'icon' => $this->nullableString($row['icon'] ?? null),
            'can' => $this->nullableString($row['can'] ?? null),
            'is_active' => $this->normalizeBoolean($row['is_active'] ?? true),
            'sort_order' => $this->nullableInteger($row['sort_order'] ?? null),
        ];
    }

    private function validateExcelRows(BaseCollection $rows, array &$report): void
    {
        $keys = [];

        foreach ($rows as $index => $row) {
            $rowNumber = (string) ($index + 2);

            if (! $row['key']) {
                $this->addError($report, $rowNumber, 'key', null, 'Key menu khong duoc de trong.');
            }

            if (! $row['name']) {
                $this->addError($report, $rowNumber, 'name', null, 'Ten menu khong duoc de trong.');
            }

            $keys[] = $row['key'];
        }

        $keySet = array_filter($keys);

        foreach ($rows as $index => $row) {
            if (! $row['parent_key']) {
                continue;
            }

            if (in_array($row['parent_key'], $keySet, true)) {
                continue;
            }

            $parentExists = AdminMenu::query()
                ->where('slug', $row['parent_key'])
                ->exists();

            if (! $parentExists) {
                $this->addError(
                    $report,
                    (string) ($index + 2),
                    'parent_key',
                    $row['parent_key'],
                    'Parent key khong ton tai trong file hoac database.'
                );
            }
        }
    }

    private function rowHasData(array $row): bool
    {
        foreach ($this->headers as $header) {
            if (($row[$header] ?? null) !== null && $row[$header] !== '') {
                return true;
            }
        }

        return false;
    }

    private function query(array $filters = [])
    {
        $query = AdminMenu::menu();

        $search = trim((string) ($filters['search'] ?? ''));

        if ($search !== '') {
            $query->where(function ($q) use ($search): void {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('url', 'like', "%{$search}%");
            });
        }

        $status = $filters['status'] ?? 'all';

        if ($status === 'active') {
            $query->where('is_active', true);
        } elseif ($status === 'inactive') {
            $query->where('is_active', false);
        }

        return $query->orderBy('sort_order');
    }

    private function validateReadableFile(string $filePath): void
    {
        if (! File::exists($filePath)) {
            throw new \RuntimeException('File import menu khong ton tai.');
        }

        if (! is_readable($filePath)) {
            throw new \RuntimeException('File import menu khong doc duoc.');
        }

        $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        if (! in_array($extension, ['xlsx', 'csv', 'json'], true)) {
            throw new \RuntimeException('Menu import chi ho tro file .xlsx, .csv hoac .json.');
        }
    }

    private function validateTree(array $items, array &$report, string $path = 'root'): void
    {
        foreach ($items as $index => $item) {
            $row = $this->nodeRow($path, $index);
            $report['total_rows']++;

            if (! is_array($item)) {
                $this->addError($report, $row, null, null, 'Menu item phai la object.');
                continue;
            }

            if (trim((string) ($item['name'] ?? '')) === '') {
                $this->addError($report, $row, 'name', $item['name'] ?? null, 'Ten menu khong duoc de trong.');
            }

            foreach (['url', 'icon', 'can'] as $field) {
                if (array_key_exists($field, $item) && ! is_null($item[$field]) && ! is_scalar($item[$field])) {
                    $this->addError($report, $row, $field, null, "Truong {$field} phai la chuoi hoac null.");
                }
            }

            if (array_key_exists('is_active', $item) && ! is_bool($item['is_active']) && ! in_array($item['is_active'], [0, 1, '0', '1'], true)) {
                $this->addError($report, $row, 'is_active', $item['is_active'], 'Trang thai menu khong hop le.');
            }

            if (array_key_exists('children', $item) && ! is_array($item['children'])) {
                $this->addError($report, $row, 'children', null, 'Children phai la mang.');
                continue;
            }

            $children = $item['children'] ?? [];

            if (! empty($children)) {
                $this->validateTree($children, $report, $row);
            }
        }
    }

    private function persistJsonNode(array $item, ?int $parentId, int $sort, string $mode, array &$report): AdminMenu
    {
        $name = trim((string) $item['name']);
        $slug = $this->normalizeKey($item['key'] ?? $item['slug'] ?? $name);
        $data = [
            'name' => $name,
            'slug' => $slug,
            'url' => $this->nullableString($item['url'] ?? null),
            'icon' => $this->nullableString($item['icon'] ?? null),
            'can' => $this->nullableString($item['can'] ?? null),
            'parent_id' => $parentId,
            'is_active' => $this->normalizeBoolean($item['is_active'] ?? true),
            'sort_order' => $sort,
        ];

        $menu = AdminMenu::query()
            ->where('slug', $slug)
            ->first();

        if ($menu && $mode === 'skip_duplicate') {
            $report['skipped_rows']++;
        } elseif ($menu) {
            $menu->update($data);
            $report['success_rows']++;
        } else {
            $menu = AdminMenu::query()->create($data);
            $report['success_rows']++;
        }

        foreach (($item['children'] ?? []) as $index => $child) {
            $this->persistJsonNode($child, (int) $menu->getKey(), $index, $mode, $report);
        }

        return $menu;
    }

    private function menuKey(AdminMenu $menu): string
    {
        return $this->normalizeKey($menu->slug ?: $menu->name) ?: 'menu-' . $menu->getKey();
    }

    private function normalizeHeader(string $header): string
    {
        return Str::of($header)->trim()->lower()->snake()->toString();
    }

    private function normalizeKey(mixed $value): ?string
    {
        $value = $this->nullableString($value);

        if ($value === null) {
            return null;
        }

        return Str::slug($value);
    }

    private function nullableString(mixed $value): ?string
    {
        if (is_null($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    private function nullableInteger(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return is_numeric($value) ? (int) $value : null;
    }

    private function normalizeBoolean(mixed $value): bool
    {
        return filter_var($value, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? false;
    }

    private function blankReport(string $mode, string $source, string $format): array
    {
        return [
            'success' => false,
            'total_rows' => 0,
            'success_rows' => 0,
            'error_rows' => 0,
            'skipped_rows' => 0,
            'errors' => [],
            'debug' => [
                'mode' => $mode,
                'source' => $source,
                'format' => $format,
                'sheets' => [$this->sheet],
            ],
        ];
    }

    private function addError(array &$report, ?string $row, ?string $column, mixed $value, string $reason): void
    {
        $report['error_rows']++;
        $report['errors'][] = [
            'sheet' => $this->sheet,
            'row' => $row,
            'column' => $column,
            'value' => is_scalar($value) || is_null($value) ? $value : null,
            'reason' => $reason,
        ];
    }

    private function nodeRow(string $path, int $index): string
    {
        return $path . '.' . ($index + 1);
    }
}
