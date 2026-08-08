<?php

namespace Modules\Account\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Modules\Account\Models\CustomerProfile;
use Modules\Account\Models\EmployeeProfile;
use Modules\Account\Models\User;
use Modules\Account\Models\UserIdentityProfile;
use Rap2hpoutre\FastExcel\FastExcel;
use Spatie\Permission\Models\Role;
use Throwable;

class AccountImportService
{
    protected array $errors = [];

    protected int $totalRows = 0;

    protected int $successRows = 0;

    protected array $requiredSheets = [
        'users',
        'employee_profiles',
        'customer_profiles',
        'user_identity_profiles',
        'user_roles',
    ];

    public function import(string $filePath): array
    {
        $this->errors = [];
        $this->totalRows = 0;
        $this->successRows = 0;

        try {
            $importedSheets = collect((new FastExcel())->importSheets($filePath));

            $sheets = $importedSheets->mapWithKeys(function ($rows, $sheetName) {
                if (is_numeric($sheetName)) {
                    return [(int) $sheetName => collect($rows)];
                }

                $normalizedName = str($sheetName)
                    ->trim()
                    ->lower()
                    ->replace(' ', '_')
                    ->toString();

                return [$normalizedName => collect($rows)];
            });

            if ($sheets->keys()->every(fn($key) => is_int($key))) {
                $sheets = $sheets->values()->mapWithKeys(function ($rows, $index) {
                    $sheetName = $this->requiredSheets[$index] ?? 'unknown_' . $index;

                    return [$sheetName => collect($rows)];
                });
            }

            $rows = [
                'users' => $this->getSheetRows($sheets, 'users'),
                'employee_profiles' => $this->getSheetRows($sheets, 'employee_profiles'),
                'customer_profiles' => $this->getSheetRows($sheets, 'customer_profiles'),
                'user_identity_profiles' => $this->getSheetRows($sheets, 'user_identity_profiles'),
                'user_roles' => $this->getSheetRows($sheets, 'user_roles'),
            ];

            $this->validateRequiredSheets($sheets);
            $this->validateUsers($rows['users']);
            $this->validateEmployeeProfiles($rows['employee_profiles'], $rows['users']);
            $this->validateCustomerProfiles($rows['customer_profiles'], $rows['users']);
            $this->validateIdentityProfiles($rows['user_identity_profiles'], $rows['users']);
            $this->validateUserRoles($rows['user_roles'], $rows['users']);

            if (! empty($this->errors)) {
                return $this->report(false);
            }

            DB::transaction(function () use ($rows) {
                $this->importUsers($rows['users']);
                $this->importEmployeeProfiles($rows['employee_profiles']);
                $this->importCustomerProfiles($rows['customer_profiles']);
                $this->importIdentityProfiles($rows['user_identity_profiles']);
                $this->importUserRoles($rows['user_roles']);
            });

            return $this->report(true);
        } catch (Throwable $e) {
            $this->addError(
                sheet: 'system',
                row: null,
                column: null,
                reason: $e->getMessage()
            );

            return $this->report(false);
        }
    }

    protected function validateRequiredSheets(Collection $sheets): void
    {
        foreach ($this->requiredSheets as $sheetName) {
            if (! $sheets->has($sheetName)) {
                $this->addError(
                    $sheetName,
                    null,
                    null,
                    'Thiếu sheet bắt buộc. Sheet tìm thấy: ' . $sheets->keys()->implode(', ')
                );
            }
        }
    }

    protected function validateUsers(Collection $rows): void
    {
        $emails = [];

        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $this->totalRows++;

            $email = $this->clean($row['email'] ?? null);
            $row['email'] = $email;
            $row['name'] = $this->clean($row['name'] ?? null);
            $row['account_type'] = $this->clean($row['account_type'] ?? null) ?: 'customer';
            $row['is_active'] = $this->clean($row['is_active'] ?? null);
            $row['phone'] = $this->clean($row['phone'] ?? null);
            $row['password'] = $this->clean($row['password'] ?? null);

            $validator = Validator::make($row, [
                'email' => ['required', 'email'],
                'name' => ['required', 'string', 'max:255'],
                'account_type' => ['nullable', 'in:employee,customer,collaborator'],
                'is_active' => ['nullable', 'in:1,0'],
                'phone' => ['nullable', 'string', 'max:50'],
                'password' => ['nullable', 'string', 'min:6'],
            ]);

            $this->pushValidatorErrors('users', $line, $validator);

            if ($email && in_array($email, $emails, true)) {
                $this->addError('users', $line, 'email', 'Email bị trùng trong file import.');
            }

            $emails[] = $email;

            $user = $this->findUserByEmail($email);

            if ($user && $this->isSuperAdmin($user)) {
                if ((string) ($row['is_active'] ?? '1') !== '1') {
                    $this->addError('users', $line, 'is_active', 'Không được khóa hoặc ngưng kích hoạt tài khoản Super Admin.');
                }

                if (! empty($row['password'])) {
                    $this->addError('users', $line, 'password', 'Không được ghi đè mật khẩu tài khoản Super Admin.');
                }
            }
        }
    }

    protected function validateEmployeeProfiles(Collection $rows, Collection $userRows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $this->totalRows++;

            $email = $this->clean($row['email'] ?? null);

            $validator = Validator::make($row, [
                'email' => ['required', 'email'],
                'employee_code' => ['required', 'string', 'max:100'],
                'department' => ['nullable', 'string', 'max:255'],
                'position' => ['nullable', 'string', 'max:255'],
                'hire_date' => ['nullable', 'date'],
                'avatar_4x6_path' => ['nullable', 'string', 'max:500'],
            ]);

            $this->pushValidatorErrors('employee_profiles', $line, $validator);

            if (! $this->emailExistsInFileOrDatabase($email, $userRows)) {
                $this->addError('employee_profiles', $line, 'email', 'Email không tồn tại trong sheet users hoặc database.');
            }

            if ($this->getAccountType($email, $userRows) !== 'employee') {
                $this->addError('employee_profiles', $line, 'email', 'Chỉ được import employee_profiles cho user có account_type = employee.');
            }
        }
    }

    protected function validateCustomerProfiles(Collection $rows, Collection $userRows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $this->totalRows++;

            $email = $this->clean($row['email'] ?? null);

            $validator = Validator::make($row, [
                'email' => ['required', 'email'],
                'customer_code' => ['required', 'string', 'max:100'],
                'customer_group' => ['nullable', 'string', 'max:255'],
                'company_name' => ['nullable', 'string', 'max:255'],
                'tax_code' => ['nullable', 'string', 'max:100'],
                'address' => ['nullable', 'string', 'max:500'],
            ]);

            $this->pushValidatorErrors('customer_profiles', $line, $validator);

            if (! $this->emailExistsInFileOrDatabase($email, $userRows)) {
                $this->addError('customer_profiles', $line, 'email', 'Email không tồn tại trong sheet users hoặc database.');
            }

            if ($this->getAccountType($email, $userRows) !== 'customer') {
                $this->addError('customer_profiles', $line, 'email', 'Chỉ được import customer_profiles cho user có account_type = customer.');
            }
        }
    }

    protected function validateIdentityProfiles(Collection $rows, Collection $userRows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $this->totalRows++;

            $email = $this->clean($row['email'] ?? null);

            $validator = Validator::make($row, [
                'email' => ['required', 'email'],
                'identity_type' => ['required', 'in:cccd,cmnd,passport,tax_code'],
                'identity_number' => ['required', 'string', 'max:100'],
                'full_name' => ['nullable', 'string', 'max:255'],
                'issued_date' => ['nullable', 'date'],
                'issued_place' => ['nullable', 'string', 'max:255'],
                'front_image_path' => ['nullable', 'string', 'max:500'],
                'back_image_path' => ['nullable', 'string', 'max:500'],
                'note' => ['nullable', 'string', 'max:1000'],
            ]);

            $this->pushValidatorErrors('user_identity_profiles', $line, $validator);

            if (! $this->emailExistsInFileOrDatabase($email, $userRows)) {
                $this->addError('user_identity_profiles', $line, 'email', 'Email không tồn tại trong sheet users hoặc database.');
            }
        }
    }

    protected function validateUserRoles(Collection $rows, Collection $userRows): void
    {
        foreach ($rows as $index => $row) {
            $line = $index + 2;
            $this->totalRows++;

            $email = $this->clean($row['email'] ?? null);
            $roleName = $this->clean($row['role_name'] ?? null) ?: 'user';
            $guardName = $this->clean($row['guard_name'] ?? null) ?: 'admin';

            $validator = Validator::make([
                'email' => $email,
                'role_name' => $roleName,
                'guard_name' => $guardName,
            ], [
                'email' => ['required', 'email'],
                'role_name' => ['nullable', 'string', 'max:255'],
                'guard_name' => ['nullable', 'string', 'max:50'],
            ]);

            $this->pushValidatorErrors('user_roles', $line, $validator);

            if (! $this->emailExistsInFileOrDatabase($email, $userRows)) {
                $this->addError('user_roles', $line, 'email', 'Email không tồn tại trong sheet users hoặc database.');
            }


            $user = $this->findUserByEmail($email);

            if ($user && $this->isSuperAdmin($user) && $roleName !== 'Super Admin') {
                $this->addError('user_roles', $line, 'role_name', 'Không được ghi đè role của tài khoản Super Admin.');
            }
        }
    }

    protected function importUsers(Collection $rows): void
    {
        foreach ($rows as $row) {
            $email = $this->clean($row['email'] ?? null);
            $user = $this->findUserByEmail($email);

            if ($user && $this->isSuperAdmin($user)) {
                $this->successRows++;
                continue;
            }

            $payload = [
                'name' => $this->clean($row['name'] ?? null),
                'email' => $email,
                'account_type' => $this->clean($row['account_type'] ?? null),
                'phone' => $this->clean($row['phone'] ?? null),
                'is_active' => (bool) ((int) ($row['is_active'] ?? 1)),
                'note' => $this->clean($row['note'] ?? null),
            ];

            if (! empty($row['password'])) {
                $payload['password'] = Hash::make((string) $row['password']);
            } elseif (! $user) {
                $payload['password'] = Hash::make(Str::random(12));
            }

            User::query()->updateOrCreate(
                ['email' => $email],
                $payload
            );

            $this->successRows++;
        }
    }

    protected function importEmployeeProfiles(Collection $rows): void
    {
        foreach ($rows as $row) {
            $user = $this->findUserByEmail($row['email'] ?? null);

            EmployeeProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $this->clean($row['employee_code'] ?? null),
                    'department' => $this->clean($row['department'] ?? null),
                    'position' => $this->clean($row['position'] ?? null),
                    'hire_date' => $this->dateOrNull($row['hire_date'] ?? null),
                    'avatar_4x6_path' => $this->clean($row['avatar_4x6_path'] ?? null),
                ]
            );

            $this->successRows++;
        }
    }

    protected function importCustomerProfiles(Collection $rows): void
    {
        foreach ($rows as $row) {
            $user = $this->findUserByEmail($row['email'] ?? null);

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'customer_code' => $this->clean($row['customer_code'] ?? null),
                    'customer_group' => $this->clean($row['customer_group'] ?? null),
                    'company_name' => $this->clean($row['company_name'] ?? null),
                    'tax_code' => $this->clean($row['tax_code'] ?? null),
                    'address' => $this->clean($row['address'] ?? null),
                ]
            );

            $this->successRows++;
        }
    }

    protected function importIdentityProfiles(Collection $rows): void
    {
        foreach ($rows as $row) {
            $user = $this->findUserByEmail($row['email'] ?? null);

            UserIdentityProfile::query()->updateOrCreate(
                [
                    'user_id' => $user->id,
                    'identity_type' => $this->clean($row['identity_type'] ?? null),
                    'identity_number' => $this->clean($row['identity_number'] ?? null),
                ],
                [
                    'full_name' => $this->clean($row['full_name'] ?? null),
                    'issued_date' => $this->dateOrNull($row['issued_date'] ?? null),
                    'issued_place' => $this->clean($row['issued_place'] ?? null),
                    'front_image_path' => $this->clean($row['front_image_path'] ?? null),
                    'back_image_path' => $this->clean($row['back_image_path'] ?? null),
                    'note' => $this->clean($row['note'] ?? null),
                ]
            );

            $this->successRows++;
        }
    }

    protected function importUserRoles(Collection $rows): void
    {
        foreach ($rows as $row) {
            $user = $this->findUserByEmail($row['email'] ?? null);

            if (! $user) {
                continue;
            }

            $roleName = $this->clean($row['role_name'] ?? null) ?: 'user';
            $guardName = $this->clean($row['guard_name'] ?? null) ?: 'admin';

            $role = Role::query()->firstOrCreate([
                'name' => $roleName,
                'guard_name' => $guardName,
            ]);

            if (! $user->hasRole($role)) {
                $user->assignRole($role);
            }

            $this->successRows++;
        }
    }

    protected function getSheetRows(Collection $sheets, string $sheetName): Collection
    {
        if (! $sheets->has($sheetName)) {
            return collect();
        }

        return collect($sheets->get($sheetName))
            ->map(fn($row) => collect($row)->mapWithKeys(
                fn($value, $key) => [Str::snake(trim((string) $key)) => $value]
            )->toArray())
            ->values();
    }

    protected function emailExistsInFileOrDatabase(?string $email, Collection $userRows): bool
    {
        if (! $email) {
            return false;
        }

        return $userRows->contains(fn($row) => $this->clean($row['email'] ?? null) === $email)
            || User::query()->where('email', $email)->exists();
    }

    protected function getAccountType(?string $email, Collection $userRows): ?string
    {
        $row = $userRows->first(fn($row) => $this->clean($row['email'] ?? null) === $email);

        if ($row) {
            return $this->clean($row['account_type'] ?? null);
        }

        return User::query()->where('email', $email)->value('account_type');
    }

    protected function findUserByEmail(?string $email): ?User
    {
        if (! $email) {
            return null;
        }

        return User::query()->where('email', $this->clean($email))->first();
    }

    protected function isSuperAdmin(User $user): bool
    {
        return method_exists($user, 'hasRole') && $user->hasRole('Super Admin');
    }

    protected function pushValidatorErrors(string $sheet, int $line, $validator): void
    {
        if ($validator->passes()) {
            return;
        }

        foreach ($validator->errors()->messages() as $column => $messages) {
            foreach ($messages as $message) {
                $this->addError($sheet, $line, $column, $message);
            }
        }
    }

    protected function addError(string $sheet, ?int $row, ?string $column, string $reason): void
    {
        $this->errors[] = [
            'sheet' => $sheet,
            'row' => $row,
            'column' => $column,
            'reason' => $reason,
        ];
    }

    protected function clean(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : $value;
    }

    protected function dateOrNull(mixed $value): ?string
    {
        $value = $this->clean($value);

        return $value ?: null;
    }

    protected function report(bool $success): array
    {
        return [
            'success' => $success,
            'total_rows' => $this->totalRows,
            'success_rows' => $success ? $this->successRows : 0,
            'error_rows' => count($this->errors),
            'errors' => $this->errors,
        ];
    }
}
