<?php

namespace Modules\Role\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Services\ImportExport\BaseImportExportService;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ImportExport extends BaseImportExportService
{
    private const ROLE_SUPER_ADMIN = 'Super Admin';

    protected string $defaultSheetName = 'roles';

    protected array $requiredHeaders = [
        'name',
        'guard_name',
    ];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'guard_name' => ['required', 'string', 'max:255'],
        'permissions' => ['nullable', 'array'],
        'permissions.*' => ['string', 'max:255'],
    ];

    protected array $uniqueBy = [
        'name',
        'guard_name',
    ];

    protected array $headerAliases = [
        'name' => ['name', 'role_name', 'ten_vai_tro', 'tên_vai_trò', 'vai_tro', 'vai_trò'],
        'guard_name' => ['guard_name', 'guard', 'bao_ve', 'bảo_vệ'],
        'permissions' => ['permissions', 'permission', 'quyen', 'quyền', 'danh_sach_quyen', 'danh_sách_quyền'],
    ];

    protected string $mode = 'update_or_create';

    public function import(string $filePath, array $options = []): array
    {
        $this->authorizeAny(['import_role', 'create_role']);
        $this->resetReport();

        $mode = $options['mode'] ?? $this->mode;
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $this->addDebug('mode', $mode);
        $this->addDebug('dry_run', $dryRun);
        $this->addDebug('file', $filePath);
        $this->addDebug('unique_by', $this->uniqueBy);

        if ($mode === 'replace') {
            $this->addError($this->defaultSheetName, null, 'mode', 'Module Role không hỗ trợ replace để tránh mất phân quyền.');

            return $this->report(false);
        }

        try {
            $this->validateImportFile($filePath);

            $rows = (new FastExcel)->import($filePath);

            $this->addDebug('sheets', [$this->defaultSheetName]);
            $this->addDebug('sheet_counts', [
                $this->defaultSheetName => $rows->count(),
            ]);

            if (! $dryRun) {
                DB::beginTransaction();
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            foreach ($rows as $index => $rawRow) {
                $rowNumber = $index + 2;
                $this->totalRows++;

                $row = $this->normalizeRowHeaders((array) $rawRow);

                if (! $this->hasRequiredHeaders($row)) {
                    $this->addError($this->defaultSheetName, $rowNumber, null, 'File thiếu cột bắt buộc.');

                    continue;
                }

                $row = $this->normalizeRow($row);

                $validator = Validator::make($row, $this->rules);

                if ($validator->fails()) {
                    foreach ($validator->errors()->messages() as $column => $messages) {
                        foreach ($messages as $message) {
                            $this->addError($this->defaultSheetName, $rowNumber, $column, $message, $row[$column] ?? null);
                        }
                    }

                    continue;
                }

                if (! $this->validateProtectedRole($row, $rowNumber)) {
                    continue;
                }

                if ($dryRun) {
                    $this->successRows++;

                    continue;
                }

                $this->persistRoleRow($row, $mode, $rowNumber);
            }

            if (! $dryRun) {
                DB::commit();
                app(PermissionRegistrar::class)->forgetCachedPermissions();
            }

            return $this->report(empty($this->errors));
        } catch (\Throwable $exception) {
            if (! $dryRun && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Role import failed', [
                'service' => static::class,
                'file' => $filePath,
                'message' => $exception->getMessage(),
            ]);

            $this->addError($this->defaultSheetName, null, null, 'Lỗi hệ thống khi import. Vui lòng kiểm tra log.');
            $this->addDebug('exception', $exception->getMessage());

            return $this->report(false);
        }
    }

    public function export(array $filters = []): string
    {
        $this->authorizeAny(['export_role', 'view_role']);

        return parent::export($filters);
    }

    public function exportTemplate(): string
    {
        $this->authorizeAny(['import_role', 'create_role']);

        return parent::exportTemplate();
    }

    protected function modelClass(): string
    {
        return Role::class;
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'name' => $this->cleanString($row['name'] ?? null),
            'guard_name' => $this->cleanString($row['guard_name'] ?? null) ?: 'admin',
            'permissions' => $this->normalizePermissions($row['permissions'] ?? null),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return Role::query()
            ->select('id', 'name', 'guard_name', 'created_at', 'updated_at')
            ->with('permissions:id,name,guard_name')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var Role $model */
        return [
            'name' => $model->name,
            'guard_name' => $model->guard_name,
            'permissions' => $model->permissions->pluck('name')->sort()->implode(', '),
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Quan ly kho',
            'guard_name' => 'admin',
            'permissions' => 'view_product, create_product, edit_product',
        ];
    }

    private function persistRoleRow(array $row, string $mode, int $rowNumber): void
    {
        $unique = [
            'name' => $row['name'],
            'guard_name' => $row['guard_name'],
        ];

        $existing = Role::query()->where($unique)->first();

        if ($mode === 'skip_duplicate' && $existing) {
            $this->skippedRows++;

            return;
        }

        if ($mode === 'create_only' && $existing) {
            $this->addError($this->defaultSheetName, $rowNumber, 'name', 'Vai trò đã tồn tại cho guard này.', $row['name']);

            return;
        }

        $role = Role::query()->updateOrCreate($unique, $unique);

        if (! empty($row['permissions'])) {
            $permissionNames = $this->permissionNamesForSync($row['permissions'], $row['guard_name']);
            $role->syncPermissions($permissionNames);
        }

        $this->successRows++;
    }

    private function validateProtectedRole(array $row, int $rowNumber): bool
    {
        if ($row['name'] !== self::ROLE_SUPER_ADMIN || $this->actorIsSuperAdmin()) {
            return true;
        }

        $this->addError($this->defaultSheetName, $rowNumber, 'name', 'Bạn không có quyền import/cập nhật vai trò Super Admin.', $row['name']);

        return false;
    }

    private function normalizePermissions(mixed $value): array
    {
        if (is_array($value)) {
            $permissions = $value;
        } else {
            $permissions = preg_split('/[,;|]+/', (string) $value) ?: [];
        }

        return collect($permissions)
            ->map(fn (mixed $permission): ?string => $this->cleanString($permission))
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    private function permissionNamesForSync(array $permissions, string $guardName): array
    {
        return collect($permissions)
            ->map(function (string $permission) use ($guardName): string {
                return Permission::query()->firstOrCreate([
                    'name' => $permission,
                    'guard_name' => $guardName,
                ])->name;
            })
            ->all();
    }

    private function actorIsSuperAdmin(): bool
    {
        $actor = auth('admin')->user();

        return $actor instanceof User && $actor->hasRole(self::ROLE_SUPER_ADMIN);
    }

    private function authorizeAny(array $permissions): void
    {
        $actor = auth('admin')->user();

        abort_unless(
            auth('admin')->check()
            && collect($permissions)->contains(fn (string $permission): bool => $actor->can($permission)),
            403
        );
    }
}
