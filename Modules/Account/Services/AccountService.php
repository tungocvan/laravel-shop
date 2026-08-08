<?php

namespace Modules\Account\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Rap2hpoutre\FastExcel\FastExcel;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Account\Models\CustomerProfile;
use Modules\Account\Models\EmployeeProfile;
use Modules\Account\Models\User;
use Modules\Account\Models\UserIdentityProfile;



class AccountService
{
    public function paginate(array $filters = [], int|string $perPage = 10): LengthAwarePaginator|Collection
    {
        $query = User::query()
            ->with([
                'accountRoles',
                'employeeProfile',
                'customerProfile',
                'identityProfile',
            ])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['account_type'] ?? null, function ($query, string $type) {
                $query->where('account_type', $type);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
            ->latest('id');

        if ($perPage === 'All') {
            return $query->get();
        }




        return $query->paginate((int) $perPage);
    }

    public function find(int $id): User
    {
        return User::query()
            ->with([
                'accountRoles',
                'employeeProfile',
                'customerProfile',
                'identityProfile',
                'metas',
            ])
            ->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create($this->userPayload($data));

            $this->syncProfile($user, $data);
            $this->syncIdentityProfile($user, $data);

            return $user->load([
                'accountRoles',
                'employeeProfile',
                'customerProfile',
                'identityProfile',
            ]);
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->find($id);

            $user->update($this->userPayload($data, true));

            $this->syncProfile($user, $data);
            $this->syncIdentityProfile($user, $data);

            return $user->load([
                'accountRoles',
                'employeeProfile',
                'customerProfile',
                'identityProfile',
            ]);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $user = $this->find($id);

            if ($user->isSuperAdmin()) {
                throw new \RuntimeException('Không thể xóa tài khoản Super Admin.');
            }

            EmployeeProfile::query()->where('user_id', $user->id)->delete();
            CustomerProfile::query()->where('user_id', $user->id)->delete();
            UserIdentityProfile::query()->where('user_id', $user->id)->delete();

            DB::table('model_has_roles')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->delete();

            DB::table('model_has_permissions')
                ->where('model_id', $user->id)
                ->where('model_type', User::class)
                ->delete();

            $user->forceDelete();
        });
    }

    public function bulkDelete(array $ids): array
    {
        $deleted = 0;
        $skippedAdmin = 0;

        DB::transaction(function () use ($ids, &$deleted, &$skippedAdmin) {
            User::query()
                ->with('accountRoles')
                ->whereIn('id', $ids)
                ->get()
                ->each(function (User $user) use (&$deleted, &$skippedAdmin) {
                    if ($user->isSuperAdmin()) {
                        $skippedAdmin++;
                        return;
                    }

                    EmployeeProfile::query()->where('user_id', $user->id)->delete();
                    CustomerProfile::query()->where('user_id', $user->id)->delete();
                    UserIdentityProfile::query()->where('user_id', $user->id)->delete();

                    DB::table('model_has_roles')
                        ->where('model_id', $user->id)
                        ->where('model_type', User::class)
                        ->delete();

                    DB::table('model_has_permissions')
                        ->where('model_id', $user->id)
                        ->where('model_type', User::class)
                        ->delete();

                    $user->forceDelete();
                    $deleted++;
                });
        });

        return [
            'deleted' => $deleted,
            'skipped_admin' => $skippedAdmin,
        ];
    }
    public function getDeletableIds(array $filters = []): Collection
    {
        return User::query()
            ->with('accountRoles')
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['account_type'] ?? null, function ($query, string $type) {
                $query->where('account_type', $type);
            })
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', function ($query) use ($filters) {
                $query->where('is_active', (bool) $filters['is_active']);
            })
            ->latest('id')
            ->get()
            ->filter(fn(User $user) => ! $user->isSuperAdmin())
            ->pluck('id')
            ->values();
    }
    public function toggleActive(int $id): User
    {
        $user = $this->find($id);

        $user->update([
            'is_active' => ! $user->is_active,
        ]);

        return $user;
    }

    private function userPayload(array $data, bool $isUpdate = false): array
    {
        $payload = [
            'name' => $data['name'] ?? null,
            'email' => $data['email'] ?? null,
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? null,
            'account_type' => $data['account_type'] ?? 'customer',
            'is_active' => $data['is_active'] ?? true,
        ];

        if (! empty($data['password'])) {
            $payload['password'] = Hash::make($data['password']);
        }

        return $payload;
    }

    private function syncProfile(User $user, array $data): void
    {
        if ($user->account_type === 'employee') {
            EmployeeProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'employee_code' => $data['employee_code'] ?? null,
                    'department' => $data['department'] ?? null,
                    'position' => $data['position'] ?? null,
                    'joined_date' => $data['joined_date'] ?? null,
                    'work_phone' => $data['work_phone'] ?? null,
                    'work_email' => $data['work_email'] ?? null,
                    'status' => $data['employee_status'] ?? 'active',
                    'note' => $data['employee_note'] ?? null,
                ]
            );

            CustomerProfile::query()
                ->where('user_id', $user->id)
                ->delete();

            return;
        }

        if ($user->account_type === 'customer') {
            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'customer_code' => $data['customer_code'] ?? null,
                    'gender' => $data['gender'] ?? null,
                    'birthday' => $data['birthday'] ?? null,
                    'address' => $data['address'] ?? null,
                    'province' => $data['province'] ?? null,
                    'district' => $data['district'] ?? null,
                    'ward' => $data['ward'] ?? null,
                    'status' => $data['customer_status'] ?? 'active',
                    'note' => $data['customer_note'] ?? null,
                ]
            );

            EmployeeProfile::query()
                ->where('user_id', $user->id)
                ->delete();
        }
    }
    public function exportRows(array $filters = []): Collection
    {
        return User::query()
            ->with(['employeeProfile', 'customerProfile'])
            ->when($filters['search'] ?? null, function ($query, string $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['account_type'] ?? null, fn($query, string $type) => $query->where('account_type', $type))
            ->when(isset($filters['is_active']) && $filters['is_active'] !== '', fn($query) => $query->where('is_active', (bool) $filters['is_active']))
            ->latest('id')
            ->get()
            ->map(function (User $user) {
                return [
                    'ID' => $user->id,
                    'Loại tài khoản' => $user->account_type,
                    'Họ tên' => $user->name,
                    'Email' => $user->email,
                    'Số điện thoại' => $user->phone,
                    'Trạng thái' => $user->is_active ? 'active' : 'inactive',

                    'Mã nhân viên' => $user->employeeProfile?->employee_code,
                    'Phòng ban' => $user->employeeProfile?->department,
                    'Chức vụ' => $user->employeeProfile?->position,
                    'Ngày vào làm' => optional($user->employeeProfile?->joined_date)->format('Y-m-d'),

                    'Mã khách hàng' => $user->customerProfile?->customer_code,
                    'Giới tính' => $user->customerProfile?->gender,
                    'Ngày sinh' => optional($user->customerProfile?->birthday)->format('Y-m-d'),
                    'Địa chỉ' => $user->customerProfile?->address,
                    'Tỉnh/TP' => $user->customerProfile?->province,
                    'Quận/Huyện' => $user->customerProfile?->district,
                    'Phường/Xã' => $user->customerProfile?->ward,
                ];
            });
    }

    public function exportToExcel(array $filters = []): string
    {
        $fileName = 'accounts_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = storage_path('app/public/exports/' . $fileName);

        if (! is_dir(dirname($filePath))) {
            mkdir(dirname($filePath), 0755, true);
        }

        (new FastExcel($this->exportRows($filters)))->export($filePath);

        return $filePath;
    }

    public function importFromExcel(string $filePath): int
    {
        $rows = (new FastExcel)->import($filePath);

        $count = 0;

        DB::transaction(function () use ($rows, &$count) {
            foreach ($rows as $row) {
                $email = trim((string) ($row['Email'] ?? ''));

                if ($email === '') {
                    continue;
                }

                $accountType = trim((string) ($row['Loại tài khoản'] ?? 'customer'));

                $user = User::query()->updateOrCreate(
                    ['email' => $email],
                    [
                        'name' => $row['Họ tên'] ?? null,
                        'phone' => $row['Số điện thoại'] ?? null,
                        'account_type' => in_array($accountType, ['employee', 'customer'], true)
                            ? $accountType
                            : 'customer',
                        'is_active' => ($row['Trạng thái'] ?? 'active') === 'active',
                    ]
                );

                $this->syncProfile($user, [
                    'account_type' => $user->account_type,

                    'employee_code' => $row['Mã nhân viên'] ?? null,
                    'department' => $row['Phòng ban'] ?? null,
                    'position' => $row['Chức vụ'] ?? null,
                    'joined_date' => $row['Ngày vào làm'] ?? null,
                    'employee_status' => 'active',

                    'customer_code' => $row['Mã khách hàng'] ?? null,
                    'gender' => $row['Giới tính'] ?? null,
                    'birthday' => $row['Ngày sinh'] ?? null,
                    'address' => $row['Địa chỉ'] ?? null,
                    'province' => $row['Tỉnh/TP'] ?? null,
                    'district' => $row['Quận/Huyện'] ?? null,
                    'ward' => $row['Phường/Xã'] ?? null,
                    'customer_status' => 'active',
                ]);

                $count++;
            }
        });

        return $count;
    }
    public function paginateForAdmin(array $filters = [])
    {
        $query = User::query()
            ->with(['employeeProfile', 'customerProfile', 'identityProfiles', 'roles'])
            ->when($filters['search'] ?? null, function ($query, $search) {
                $query->where(function ($subQuery) use ($search) {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn($query, $status) => $query->where('status', $status))
            ->when($filters['account_type'] ?? null, fn($query, $type) => $query->where('account_type', $type))
            ->latest();

        if (($filters['per_page'] ?? 10) === 'All') {
            return $query->get();
        }

        return $query->paginate((int) ($filters['per_page'] ?? 10));
    }

    private function syncIdentityProfile(User $user, array $data): void
    {
        $hasIdentityData = collect([
            $data['identity_type'] ?? null,
            $data['identity_number'] ?? null,
            $data['issued_date'] ?? null,
            $data['issued_place'] ?? null,
            $data['front_image'] ?? null,
            $data['back_image'] ?? null,
            $data['portrait_4x6_image'] ?? null,
            $data['tax_code'] ?? null,
            $data['tax_registered_name'] ?? null,
            $data['tax_address'] ?? null,
            $data['identity_note'] ?? null,
        ])->filter(fn($value) => filled($value))->isNotEmpty();

        if (! $hasIdentityData) {
            return;
        }

        UserIdentityProfile::query()->updateOrCreate(
            ['user_id' => $user->id],
            [
                'identity_type' => $data['identity_type'] ?? null,
                'identity_number' => $data['identity_number'] ?? null,
                'issued_date' => $data['issued_date'] ?? null,
                'issued_place' => $data['issued_place'] ?? null,
                'front_image' => $data['front_image'] ?? null,
                'back_image' => $data['back_image'] ?? null,
                'portrait_4x6_image' => $data['portrait_4x6_image'] ?? null,
                'tax_code' => $data['tax_code'] ?? null,
                'tax_registered_name' => $data['tax_registered_name'] ?? null,
                'tax_address' => $data['tax_address'] ?? null,
                'note' => $data['identity_note'] ?? null,
            ]
        );
    }
    public function export(array $filters = [])
    {
        $filePath = $this->exportToExcel($filters);

        return response()->download($filePath)->deleteFileAfterSend(false);
    }
}
