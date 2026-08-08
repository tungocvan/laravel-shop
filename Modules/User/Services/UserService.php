<?php

namespace Modules\User\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserService
{
    private const ROLE_SUPER_ADMIN = 'Super Admin';

    public function paginateStaff(array $filters, User $actor): LengthAwarePaginator
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return $this->staffQuery($filters, $actor)
            ->latest()
            ->paginate($perPage);
    }

    public function availableRoles(User $actor): Collection
    {
        return Role::query()
            ->select('id', 'name', 'guard_name')
            ->where('guard_name', 'admin')
            ->when(! $this->isSuperAdmin($actor), fn (Builder $query) => $query->where('name', '!=', self::ROLE_SUPER_ADMIN))
            ->orderBy('name')
            ->get();
    }

    public function findStaff(int $id, User $actor): User
    {
        return $this->staffQuery([], $actor)->findOrFail($id);
    }

    public function saveStaff(array $data, ?int $id, User $actor): User
    {
        return DB::transaction(function () use ($data, $id, $actor): User {
            $existingRoles = [];

            if ($id) {
                $user = $this->findStaff($id, $actor);
                $existingRoles = $user->roles->pluck('name')->all();
            } else {
                $user = new User;
            }

            $roles = $this->allowedRoleNames((array) ($data['roles'] ?? []), $actor, $existingRoles);

            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            if (! empty($data['password'])) {
                $user->password = Hash::make($data['password']);
            }

            $user->save();
            $user->syncRoles($roles);

            return $user->load('roles');
        });
    }

    public function deleteStaff(int $id, User $actor): void
    {
        DB::transaction(function () use ($id, $actor): void {
            if ($id === $actor->id) {
                throw new \RuntimeException('Không thể xoá tài khoản đang đăng nhập.');
            }

            $user = $this->findStaff($id, $actor);
            $user->delete();
        });
    }

    public function deleteMany(array $ids, User $actor): int
    {
        $ids = collect($ids)->map(fn ($id) => (int) $id)->filter()->unique()->values();

        if ($ids->contains($actor->id)) {
            throw new \RuntimeException('Không thể xoá tài khoản đang đăng nhập.');
        }

        return DB::transaction(function () use ($ids, $actor): int {
            $users = $this->staffQuery([], $actor)
                ->whereIn('id', $ids)
                ->get();

            $users->each->delete();

            return $users->count();
        });
    }

    public function selectedPageIds(array $filters, User $actor): array
    {
        $perPage = min(max((int) ($filters['per_page'] ?? 10), 1), 100);

        return $this->staffQuery($filters, $actor)
            ->paginate($perPage)
            ->pluck('id')
            ->map(fn (int $id): string => (string) $id)
            ->all();
    }

    private function staffQuery(array $filters, User $actor): Builder
    {
        return User::query()
            ->select('id', 'name', 'email', 'phone', 'is_active', 'created_at')
            ->with('roles:id,name,guard_name')
            ->whereHas('roles')
            ->when(! $this->isSuperAdmin($actor), function (Builder $query): void {
                $query->whereDoesntHave('roles', fn (Builder $roles) => $roles->whereRaw('LOWER(name) = ?', ['super admin']));
            })
            ->when($filters['search'] ?? null, function (Builder $query, string $search): void {
                $query->where(function (Builder $subQuery) use ($search): void {
                    $subQuery->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%")
                        ->orWhere('phone', 'like', "%{$search}%");
                });
            })
            ->when($filters['role'] ?? null, fn (Builder $query, string $role) => $query->whereHas('roles', fn (Builder $roles) => $roles->whereKey($role)));
    }

    private function allowedRoleNames(array $roleNames, User $actor, array $existingRoles): array
    {
        $roleNames = array_values(array_unique(array_filter($roleNames)));

        if (! $this->isSuperAdmin($actor) && in_array(self::ROLE_SUPER_ADMIN, $roleNames, true) && ! in_array(self::ROLE_SUPER_ADMIN, $existingRoles, true)) {
            throw new \RuntimeException('Bạn không có quyền gán vai trò Super Admin.');
        }

        if (! $this->isSuperAdmin($actor)) {
            $roleNames = array_values(array_filter($roleNames, fn (string $role): bool => $role !== self::ROLE_SUPER_ADMIN));
        }

        return Role::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $roleNames)
            ->pluck('name')
            ->all();
    }

    private function isSuperAdmin(User $user): bool
    {
        return $user->hasRole(self::ROLE_SUPER_ADMIN);
    }
}
