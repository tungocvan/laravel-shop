<?php

namespace Modules\Identity\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\CustomerProfile;
use Modules\Identity\Models\EmployeeProfile;
use Modules\Identity\Models\User;
use Modules\Identity\Models\UserIdentityProfile;

class IdentityService
{
    public function paginateForAdmin(array $filters = []): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 15), 1), 100);

        return $this->query($filters)
            ->latest('id')
            ->paginate($perPage);
    }

    public function find(int $id): User
    {
        return User::query()
            ->with(['roles', 'employeeProfile', 'customerProfile', 'identityProfile', 'metas', 'addresses'])
            ->findOrFail($id);
    }

    public function create(array $data): User
    {
        return DB::transaction(function () use ($data) {
            $user = User::query()->create($this->userPayload($data));

            $this->syncProfile($user, $data);
            $this->syncIdentityProfile($user, $data);

            return $this->find($user->id);
        });
    }

    public function update(int $id, array $data): User
    {
        return DB::transaction(function () use ($id, $data) {
            $user = $this->find($id);

            if ($user->isSuperAdmin() && array_key_exists('is_active', $data) && ! (bool) $data['is_active']) {
                throw new \RuntimeException('Super Admin accounts cannot be deactivated.');
            }

            $user->update($this->userPayload($data));
            $this->syncProfile($user, $data);
            $this->syncIdentityProfile($user, $data);

            return $this->find($user->id);
        });
    }

    public function delete(int $id): void
    {
        DB::transaction(function () use ($id) {
            $user = $this->find($id);

            if ($user->isSuperAdmin()) {
                throw new \RuntimeException('Super Admin accounts cannot be deleted.');
            }

            $user->roles()->detach();
            $user->permissions()->detach();
            $user->delete();
        });
    }

    public function activate(int $id): User
    {
        return $this->setActive($id, true);
    }

    public function deactivate(int $id): User
    {
        return $this->setActive($id, false);
    }

    private function setActive(int $id, bool $active): User
    {
        return DB::transaction(function () use ($id, $active) {
            $user = $this->find($id);

            if (! $active && $user->isSuperAdmin()) {
                throw new \RuntimeException('Super Admin accounts cannot be deactivated.');
            }

            $user->forceFill(['is_active' => $active])->save();

            return $this->find($user->id);
        });
    }

    private function query(array $filters)
    {
        return User::query()
            ->with(['roles', 'employeeProfile', 'customerProfile', 'identityProfile'])
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
            });
    }

    private function userPayload(array $data): array
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
            $profile = EmployeeProfile::withTrashed()->updateOrCreate(
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

            if ($profile->trashed()) {
                $profile->restore();
            }

            CustomerProfile::query()->where('user_id', $user->id)->delete();

            return;
        }

        $profile = CustomerProfile::withTrashed()->updateOrCreate(
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

        if ($profile->trashed()) {
            $profile->restore();
        }

        EmployeeProfile::query()->where('user_id', $user->id)->delete();
    }

    private function syncIdentityProfile(User $user, array $data): void
    {
        $payload = [
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
        ];

        if (collect($payload)->filter(fn ($value) => filled($value))->isEmpty()) {
            return;
        }

        $profile = UserIdentityProfile::withTrashed()->updateOrCreate(['user_id' => $user->id], $payload);

        if ($profile->trashed()) {
            $profile->restore();
        }
    }
}
