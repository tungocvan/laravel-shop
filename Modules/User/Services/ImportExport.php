<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Shared\Services\ImportExport\BaseImportExportService;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\Permission\Models\Role;

class ImportExport extends BaseImportExportService
{
    private const ROLE_SUPER_ADMIN = 'Super Admin';

    private const DEFAULT_ROLE = 'user';

    protected string $defaultSheetName = 'users';

    protected array $requiredHeaders = [
        'name',
        'email',
    ];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'password' => ['nullable', 'string', 'min:8'],
        'is_active' => ['nullable', 'boolean'],
        'roles' => ['nullable', 'array'],
        'roles.*' => ['string'],
    ];

    protected array $uniqueBy = ['email'];

    protected array $headerAliases = [
        'name' => ['name', 'ho_ten', 'họ_tên', 'ten', 'tên'],
        'email' => ['email', 'email_dang_nhap', 'email_đăng_nhập'],
        'phone' => ['phone', 'so_dien_thoai', 'số_điện_thoại', 'sdt'],
        'password' => ['password', 'mat_khau', 'mật_khẩu'],
        'is_active' => ['is_active', 'trang_thai', 'trạng_thái', 'active'],
        'roles' => ['roles', 'role', 'vai_tro', 'vai_trò'],
        'created_at' => ['created_at', 'ngay_tao', 'ngày_tạo'],
    ];

    protected string $mode = 'update_or_create';

    public function import(string $filePath, array $options = []): array
    {
        $this->authorizeAdmin('import_user');
        $this->resetReport();

        $mode = $options['mode'] ?? $this->mode;
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $this->addDebug('mode', $mode);
        $this->addDebug('dry_run', $dryRun);
        $this->addDebug('file', $filePath);
        $this->addDebug('unique_by', $this->uniqueBy);
        $this->addDebug('default_role', self::DEFAULT_ROLE);

        if ($mode === 'replace') {
            $this->addError($this->defaultSheetName, null, null, 'Module User không hỗ trợ chế độ replace để tránh mất dữ liệu.');

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

                if (! $this->validateRoles($row, $rowNumber)) {
                    continue;
                }

                if ($dryRun) {
                    $this->successRows++;

                    continue;
                }

                $this->persistUserRow($row, $mode, $rowNumber);
            }

            if (! $dryRun) {
                DB::commit();
            }

            return $this->report(empty($this->errors));
        } catch (\Throwable $exception) {
            if (! $dryRun && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('User import failed', [
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
        $this->authorizeAdmin('export_user');

        return parent::export($filters);
    }

    public function exportTemplate(): string
    {
        $this->authorizeAdmin('import_user');

        return parent::exportTemplate();
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function normalizeRow(array $row): array
    {
        return [
            'name' => $this->cleanString($row['name'] ?? null),
            'email' => mb_strtolower((string) $this->cleanString($row['email'] ?? null)),
            'phone' => $this->cleanString($row['phone'] ?? null),
            'password' => $this->cleanString($row['password'] ?? null),
            'is_active' => $this->cleanBoolean($row['is_active'] ?? true) ?? true,
            'roles' => $this->normalizeRoles($row['roles'] ?? null),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return User::query()
            ->select('id', 'name', 'email', 'phone', 'is_active', 'created_at')
            ->with('roles:id,name,guard_name')
            ->whereHas('roles')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn ($query, string $role) => $query->whereHas('roles', fn ($roles) => $roles->whereKey($role)))
            ->latest('id')
            ->get();
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var User $model */
        return [
            'name' => $model->name,
            'email' => $model->email,
            'phone' => $model->phone,
            'is_active' => (bool) $model->is_active,
            'roles' => $model->roles->pluck('name')->implode(', '),
            'created_at' => optional($model->created_at)->format('Y-m-d H:i:s'),
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.test',
            'phone' => '0900000000',
            'password' => 'password123',
            'is_active' => true,
            'roles' => self::DEFAULT_ROLE,
            'created_at' => 'Chỉ export, không import',
        ];
    }

    private function persistUserRow(array $row, string $mode, int $rowNumber): void
    {
        $existing = User::withTrashed()->where('email', $row['email'])->first();

        if ($mode === 'skip_duplicate' && $existing) {
            $this->skippedRows++;

            return;
        }

        if ($mode === 'create_only' && $existing) {
            $this->addError($this->defaultSheetName, $rowNumber, 'email', 'Email đã tồn tại.', $row['email']);

            return;
        }

        if ($existing && $this->isSuperAdmin($existing) && ! $this->actorIsSuperAdmin()) {
            $this->addError($this->defaultSheetName, $rowNumber, 'email', 'Bạn không có quyền cập nhật tài khoản Super Admin.', $row['email']);

            return;
        }

        $payload = [
            'name' => $row['name'],
            'email' => $row['email'],
            'phone' => $row['phone'],
            'is_active' => (bool) $row['is_active'],
        ];

        if (! empty($row['password'])) {
            $payload['password'] = Hash::make($row['password']);
        }

        $user = $existing ?: new User;
        $user->fill($payload);

        if ($user->trashed()) {
            $user->restore();
        }

        $user->save();
        $this->syncAdminRoles($user, $row['roles']);

        $this->successRows++;
    }

    private function validateRoles(array $row, int $rowNumber): bool
    {
        $roles = $row['roles'];

        if (! $this->actorIsSuperAdmin() && in_array(self::ROLE_SUPER_ADMIN, $roles, true)) {
            $this->addError($this->defaultSheetName, $rowNumber, 'roles', 'Bạn không có quyền import/gán vai trò Super Admin.', implode(', ', $roles));

            return false;
        }

        return true;
    }

    private function normalizeRoles(mixed $value): array
    {
        if (is_array($value)) {
            $roles = $value;
        } else {
            $roles = preg_split('/[,;|]+/', (string) $value) ?: [];
        }

        $roles = collect($roles)
            ->map(fn (mixed $role): ?string => $this->cleanString($role))
            ->filter()
            ->unique()
            ->values()
            ->all();

        return $roles === [] ? [self::DEFAULT_ROLE] : $roles;
    }

    private function syncAdminRoles(User $user, array $roles): void
    {
        $roleIds = $this->adminRoleIds($roles);

        DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))
            ->where('model_type', $user->getMorphClass())
            ->where('model_id', $user->getKey())
            ->delete();

        foreach ($roleIds as $roleId) {
            DB::table(config('permission.table_names.model_has_roles', 'model_has_roles'))->insert([
                'role_id' => $roleId,
                'model_type' => $user->getMorphClass(),
                'model_id' => $user->getKey(),
            ]);
        }

        $user->unsetRelation('roles');
    }

    private function adminRoleIds(array $roles): array
    {
        return collect($roles)
            ->map(function (string $role): int {
                return Role::query()->firstOrCreate([
                    'name' => $role,
                    'guard_name' => 'admin',
                ])->id;
            })
            ->all();
    }

    private function actorIsSuperAdmin(): bool
    {
        $actor = auth('admin')->user();

        return $actor instanceof User && $this->isSuperAdmin($actor);
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_ADMIN);
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
