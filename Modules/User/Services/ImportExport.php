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

    protected array $requiredHeaders = ['name', 'email'];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'password' => ['nullable', 'string', 'min:8'],
        'password_hash' => ['nullable', 'string', 'max:255'],
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
        'password_hash' => ['password_hash', 'mat_khau_hash', 'mật_khẩu_hash'],
        'is_active' => ['is_active', 'trang_thai', 'trạng_thái', 'active'],
        'roles' => ['roles', 'role', 'vai_tro', 'vai_trò'],
        'created_at' => ['created_at', 'ngay_tao', 'ngày_tạo'],
    ];

    protected string $mode = 'update_or_create';

    private bool $includePasswordHash = false;

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
            $this->addDebug('sheet_counts', [$this->defaultSheetName => $rows->count()]);

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

                if (! $this->validateCredentialPayload($row, $rowNumber) || ! $this->validateRoles($row, $rowNumber)) {
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

        $includePasswordHash = (bool) ($filters['include_password_hash'] ?? false);
        if ($includePasswordHash) {
            abort_unless($this->actorIsSuperAdmin(), 403, 'Chỉ Super Admin được export password_hash để backup.');
        }

        $this->includePasswordHash = $includePasswordHash;

        try {
            return parent::export($filters);
        } finally {
            $this->includePasswordHash = false;
        }
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
            'password_hash' => $this->cleanString($row['password_hash'] ?? null),
            'is_active' => $this->cleanBoolean($row['is_active'] ?? true) ?? true,
            'roles' => $this->normalizeRoles($row['roles'] ?? null),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return app(UserService::class)->exportStaff($filters, $this->actor());
    }

    protected function mapExportRow(Model $model): array
    {
        /** @var User $model */
        $row = [
            'name' => $model->name,
            'email' => $model->email,
            'phone' => $model->phone,
            'is_active' => $model->is_active ? 1 : 0,
            'roles' => $model->roles->pluck('name')->implode(', '),
            'created_at' => optional($model->created_at)->format('Y-m-d H:i:s'),
        ];

        if ($this->includePasswordHash) {
            $row['password_hash'] = $model->getRawOriginal('password');
        }

        return $row;
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.test',
            'phone' => '0900000000',
            'password' => 'password123',
            'password_hash' => null,
            'is_active' => 1,
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
        } elseif (! empty($row['password_hash'])) {
            $payload['password'] = $row['password_hash'];
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

    private function validateCredentialPayload(array $row, int $rowNumber): bool
    {
        if (! empty($row['password']) && ! empty($row['password_hash'])) {
            $this->addError($this->defaultSheetName, $rowNumber, 'password', 'Chỉ được cung cấp password hoặc password_hash, không dùng đồng thời cả hai.');

            return false;
        }

        if (empty($row['password_hash'])) {
            return true;
        }

        if (! $this->actorIsSuperAdmin()) {
            $this->addError($this->defaultSheetName, $rowNumber, 'password_hash', 'Chỉ Super Admin được import password_hash từ file backup.');

            return false;
        }

        if (! $this->looksLikePasswordHash($row['password_hash'])) {
            $this->addError($this->defaultSheetName, $rowNumber, 'password_hash', 'password_hash không đúng định dạng hash được hỗ trợ.');

            return false;
        }

        return true;
    }

    private function validateRoles(array $row, int $rowNumber): bool
    {
        $roles = $row['roles'];

        if (! $this->actorIsSuperAdmin() && in_array(self::ROLE_SUPER_ADMIN, $roles, true)) {
            $this->addError($this->defaultSheetName, $rowNumber, 'roles', 'Bạn không có quyền import/gán vai trò Super Admin.', implode(', ', $roles));

            return false;
        }

        $unknownRoles = collect($roles)
            ->diff(Role::query()->where('guard_name', 'admin')->whereIn('name', $roles)->pluck('name'))
            ->values();

        if ($unknownRoles->isNotEmpty()) {
            $this->addError($this->defaultSheetName, $rowNumber, 'roles', 'Vai trò không tồn tại trong Role catalog: '.$unknownRoles->implode(', '), implode(', ', $roles));

            return false;
        }

        return true;
    }

    private function normalizeRoles(mixed $value): array
    {
        $roles = is_array($value) ? $value : (preg_split('/[,;|]+/', (string) $value) ?: []);

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
        $adminRoles = Role::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $roles)
            ->get();

        $user->syncRoles($adminRoles);
        $user->unsetRelation('roles');
    }

    private function actorIsSuperAdmin(): bool
    {
        return $this->isSuperAdmin($this->actor());
    }

    private function actor(): User
    {
        $actor = auth('admin')->user();

        abort_unless($actor instanceof User, 403);

        return $actor;
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_ADMIN);
    }

    private function looksLikePasswordHash(string $value): bool
    {
        return str_starts_with($value, '$2y$')
            || str_starts_with($value, '$2a$')
            || str_starts_with($value, '$2b$')
            || str_starts_with($value, '$argon2i$')
            || str_starts_with($value, '$argon2id$');
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
