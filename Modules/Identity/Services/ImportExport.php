<?php

namespace Modules\Identity\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Modules\Identity\Models\CustomerProfile;
use Modules\Identity\Models\EmployeeProfile;
use Modules\Identity\Models\User;
use Modules\Identity\Models\UserIdentityProfile;
use Modules\Shared\Services\ImportExport\BaseImportExportService;
use Rap2hpoutre\FastExcel\FastExcel;

class ImportExport extends BaseImportExportService
{
    protected array $requiredHeaders = [
        'name',
        'email',
        'account_type',
    ];

    protected array $rules = [
        'name' => ['required', 'string', 'max:255'],
        'email' => ['required', 'email', 'max:255'],
        'phone' => ['nullable', 'string', 'max:50'],
        'password' => ['nullable', 'string', 'min:8'],
        'account_type' => ['required', 'in:employee,customer'],
        'is_active' => ['nullable', 'boolean'],
        'employee_code' => ['required_if:account_type,employee', 'nullable', 'string', 'max:100'],
        'department' => ['nullable', 'string', 'max:255'],
        'position' => ['nullable', 'string', 'max:255'],
        'joined_date' => ['nullable', 'date'],
        'work_phone' => ['nullable', 'string', 'max:50'],
        'work_email' => ['nullable', 'email', 'max:255'],
        'customer_code' => ['required_if:account_type,customer', 'nullable', 'string', 'max:100'],
        'gender' => ['nullable', 'string', 'max:20'],
        'birthday' => ['nullable', 'date'],
        'address' => ['nullable', 'string', 'max:500'],
        'province' => ['nullable', 'string', 'max:255'],
        'district' => ['nullable', 'string', 'max:255'],
        'ward' => ['nullable', 'string', 'max:255'],
        'identity_type' => ['nullable', 'in:citizen_id,passport,tax_code,other'],
        'identity_number' => ['nullable', 'string', 'max:100'],
        'issued_date' => ['nullable', 'date'],
        'issued_place' => ['nullable', 'string', 'max:255'],
        'tax_code' => ['nullable', 'string', 'max:100'],
        'tax_registered_name' => ['nullable', 'string', 'max:255'],
        'tax_address' => ['nullable', 'string', 'max:500'],
        'identity_note' => ['nullable', 'string', 'max:1000'],
    ];

    protected array $uniqueBy = ['email'];

    protected array $headerAliases = [
        'name' => ['name', 'ho_ten', 'họ_tên', 'ten', 'tên'],
        'email' => ['email', 'email_dang_nhap', 'email_đăng_nhập'],
        'phone' => ['phone', 'so_dien_thoai', 'số_điện_thoại', 'sdt'],
        'password' => ['password', 'mat_khau', 'mật_khẩu'],
        'account_type' => ['account_type', 'loai_tai_khoan', 'loại_tài_khoản'],
        'is_active' => ['is_active', 'trang_thai', 'trạng_thái'],
        'employee_code' => ['employee_code', 'ma_nhan_vien', 'mã_nhân_viên'],
        'customer_code' => ['customer_code', 'ma_khach_hang', 'mã_khách_hàng'],
        'identity_number' => ['identity_number', 'so_dinh_danh', 'số_định_danh'],
        'tax_code' => ['tax_code', 'ma_so_thue', 'mã_số_thuế'],
    ];

    protected string $mode = 'update_or_create';

    public function __construct(private readonly IdentityService $identities)
    {
    }

    public function import(string $filePath, array $options = []): array
    {
        $this->authorizeAdmin('import_identity');
        $this->resetReport();

        $mode = $options['mode'] ?? $this->mode;
        $dryRun = (bool) ($options['dry_run'] ?? false);

        $this->addDebug('mode', $mode);
        $this->addDebug('dry_run', $dryRun);
        $this->addDebug('file', $filePath);

        try {
            $this->validateImportFile($filePath);

            $rows = (new FastExcel())->import($filePath);

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

                if (! $this->validateUniqueProfileCodes($row, $rowNumber)) {
                    continue;
                }

                if ($dryRun) {
                    $this->successRows++;
                    continue;
                }

                $this->persistIdentityRow($row, $mode, $rowNumber);
            }

            if (! $dryRun) {
                DB::commit();
            }

            return $this->report(empty($this->errors));
        } catch (\Throwable $exception) {
            if (! $dryRun && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            Log::error('Identity import failed', [
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
        $this->authorizeAdmin('export_identity');

        return parent::export($filters);
    }

    public function exportTemplate(): string
    {
        $this->authorizeAdmin('import_identity');

        return parent::exportTemplate();
    }

    protected function modelClass(): string
    {
        return User::class;
    }

    protected function normalizeRow(array $row): array
    {
        $accountType = $this->normalizeAccountType($row['account_type'] ?? 'customer');

        return [
            'name' => $this->cleanString($row['name'] ?? null),
            'email' => mb_strtolower((string) $this->cleanString($row['email'] ?? null)),
            'phone' => $this->cleanString($row['phone'] ?? null),
            'password' => $this->cleanString($row['password'] ?? null),
            'account_type' => $accountType,
            'is_active' => $this->cleanBoolean($row['is_active'] ?? true) ?? true,
            'employee_code' => $this->cleanString($row['employee_code'] ?? null),
            'department' => $this->cleanString($row['department'] ?? null),
            'position' => $this->cleanString($row['position'] ?? null),
            'joined_date' => $this->cleanDate($row['joined_date'] ?? null),
            'work_phone' => $this->cleanString($row['work_phone'] ?? null),
            'work_email' => $this->cleanString($row['work_email'] ?? null),
            'customer_code' => $this->cleanString($row['customer_code'] ?? null),
            'gender' => $this->cleanString($row['gender'] ?? null),
            'birthday' => $this->cleanDate($row['birthday'] ?? null),
            'address' => $this->cleanString($row['address'] ?? null),
            'province' => $this->cleanString($row['province'] ?? null),
            'district' => $this->cleanString($row['district'] ?? null),
            'ward' => $this->cleanString($row['ward'] ?? null),
            'identity_type' => $this->cleanString($row['identity_type'] ?? null),
            'identity_number' => $this->cleanString($row['identity_number'] ?? null),
            'issued_date' => $this->cleanDate($row['issued_date'] ?? null),
            'issued_place' => $this->cleanString($row['issued_place'] ?? null),
            'tax_code' => $this->cleanString($row['tax_code'] ?? null),
            'tax_registered_name' => $this->cleanString($row['tax_registered_name'] ?? null),
            'tax_address' => $this->cleanString($row['tax_address'] ?? null),
            'identity_note' => $this->cleanString($row['identity_note'] ?? null),
        ];
    }

    protected function exportRows(array $filters = []): Collection
    {
        return User::query()
            ->with(['employeeProfile', 'customerProfile', 'identityProfile'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['account_type'] ?? null, fn ($query, string $type) => $query->where('account_type', $type))
            ->when(array_key_exists('is_active', $filters) && $filters['is_active'] !== null && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
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
            'account_type' => $model->account_type,
            'is_active' => (bool) $model->is_active,
            'employee_code' => $model->employeeProfile?->employee_code,
            'department' => $model->employeeProfile?->department,
            'position' => $model->employeeProfile?->position,
            'joined_date' => optional($model->employeeProfile?->joined_date)->format('Y-m-d'),
            'work_phone' => $model->employeeProfile?->work_phone,
            'work_email' => $model->employeeProfile?->work_email,
            'customer_code' => $model->customerProfile?->customer_code,
            'gender' => $model->customerProfile?->gender,
            'birthday' => optional($model->customerProfile?->birthday)->format('Y-m-d'),
            'address' => $model->customerProfile?->address,
            'province' => $model->customerProfile?->province,
            'district' => $model->customerProfile?->district,
            'ward' => $model->customerProfile?->ward,
            'identity_type' => $model->identityProfile?->identity_type,
            'identity_number' => $model->identityProfile?->identity_number,
            'issued_date' => optional($model->identityProfile?->issued_date)->format('Y-m-d'),
            'issued_place' => $model->identityProfile?->issued_place,
            'tax_code' => $model->identityProfile?->tax_code,
            'tax_registered_name' => $model->identityProfile?->tax_registered_name,
            'tax_address' => $model->identityProfile?->tax_address,
            'identity_note' => $model->identityProfile?->note,
        ];
    }

    protected function templateSampleRow(): array
    {
        return [
            'name' => 'Nguyen Van A',
            'email' => 'nguyenvana@example.test',
            'phone' => '0900000000',
            'password' => 'password123',
            'account_type' => 'customer',
            'is_active' => true,
            'employee_code' => null,
            'department' => null,
            'position' => null,
            'joined_date' => null,
            'work_phone' => null,
            'work_email' => null,
            'customer_code' => 'CUS-0001',
            'gender' => 'male',
            'birthday' => '1990-01-01',
            'address' => '123 Nguyen Trai',
            'province' => 'TP HCM',
            'district' => 'Quan 1',
            'ward' => 'Ben Nghe',
            'identity_type' => 'citizen_id',
            'identity_number' => '012345678901',
            'issued_date' => '2020-01-01',
            'issued_place' => 'Cuc CSQLHC ve TTXH',
            'tax_code' => '1234567890',
            'tax_registered_name' => 'Nguyen Van A',
            'tax_address' => '123 Nguyen Trai, TP HCM',
            'identity_note' => null,
        ];
    }

    private function persistIdentityRow(array $row, string $mode, int $rowNumber): void
    {
        $existing = User::query()->where('email', $row['email'])->first();

        if ($mode === 'skip_duplicate' && $existing) {
            $this->skippedRows++;
            return;
        }

        if ($mode === 'create_only' && $existing) {
            $this->addError($this->defaultSheetName, $rowNumber, 'email', 'Email đã tồn tại.', $row['email']);
            return;
        }

        if ($mode === 'replace' && $existing && $existing->isSuperAdmin()) {
            $this->addError($this->defaultSheetName, $rowNumber, 'email', 'Không được thay thế tài khoản Super Admin.', $row['email']);
            return;
        }

        if ($existing && $existing->isSuperAdmin()) {
            unset($row['password']);
            $row['is_active'] = true;
        }

        if ($mode === 'replace' && $existing) {
            EmployeeProfile::withTrashed()->where('user_id', $existing->id)->forceDelete();
            CustomerProfile::withTrashed()->where('user_id', $existing->id)->forceDelete();
            UserIdentityProfile::withTrashed()->where('user_id', $existing->id)->forceDelete();
        }

        $user = $existing
            ? $this->identities->update($existing->id, $row)
            : $this->identities->create($row);

        $this->successRows++;
        $this->addDebug('last_imported_id', $user->id);
    }

    private function validateUniqueProfileCodes(array $row, int $rowNumber): bool
    {
        $existingUser = User::query()->where('email', $row['email'])->first();
        $existingUserId = $existingUser?->id;
        $valid = true;

        if ($row['account_type'] === 'employee' && filled($row['employee_code'] ?? null)) {
            $profile = EmployeeProfile::withTrashed()
                ->where('employee_code', $row['employee_code'])
                ->first();

            if ($profile && $profile->user_id !== $existingUserId) {
                $this->addError(
                    $this->defaultSheetName,
                    $rowNumber,
                    'employee_code',
                    'Mã nhân viên đã thuộc tài khoản khác.',
                    $row['employee_code']
                );

                $valid = false;
            }
        }

        if ($row['account_type'] === 'customer' && filled($row['customer_code'] ?? null)) {
            $profile = CustomerProfile::withTrashed()
                ->where('customer_code', $row['customer_code'])
                ->first();

            if ($profile && $profile->user_id !== $existingUserId) {
                $this->addError(
                    $this->defaultSheetName,
                    $rowNumber,
                    'customer_code',
                    'Mã khách hàng đã thuộc tài khoản khác.',
                    $row['customer_code']
                );

                $valid = false;
            }
        }

        return $valid;
    }

    private function normalizeAccountType(mixed $value): string
    {
        $value = mb_strtolower(trim((string) $value));

        return match ($value) {
            'employee', 'nhan_vien', 'nhân_viên', 'nhân viên' => 'employee',
            default => 'customer',
        };
    }

    private function authorizeAdmin(string $permission): void
    {
        abort_unless(auth('admin')->check() && auth('admin')->user()->can($permission), 403);
    }
}
