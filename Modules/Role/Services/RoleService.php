<?php

namespace Modules\Role\Services;

use App\Modules\ModulePermissionManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleService
{
    public const ADMIN_GUARD = 'admin';

    public const PROTECTED_ROLE = 'Super Admin';

    public function __construct(
        private readonly ModulePermissionManager $modulePermissions,
    ) {
    }

    public function queryRoles(string $search = ''): Builder
    {
        return Role::query()
            ->withCount('users')
            ->where('guard_name', self::ADMIN_GUARD)
            ->when($search !== '', fn (Builder $query) => $query->where('name', 'like', '%'.$search.'%'))
            ->latest();
    }

    public function findAdminRole(int $id): Role
    {
        return Role::query()
            ->where('guard_name', self::ADMIN_GUARD)
            ->findOrFail($id);
    }

    public function approvedPermissionNames(): array
    {
        return collect($this->modulePermissions->activeGroups())
            ->flatten()
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values()
            ->all();
    }

    public function save(?int $roleId, string $name, array $selectedPermissions, array $preservedPermissions = []): Role
    {
        $role = $roleId !== null ? $this->findAdminRole($roleId) : null;

        if ($role?->name === self::PROTECTED_ROLE) {
            throw ValidationException::withMessages([
                'name' => 'Vai trò Super Admin là vai trò hệ thống và không thể chỉnh sửa bằng màn hình quản lý vai trò.',
            ]);
        }

        $approved = collect($this->approvedPermissionNames());
        $historical = $role
            ? $role->permissions->pluck('name')->diff($approved)->values()
            : collect();

        $requested = collect($selectedPermissions)
            ->merge($preservedPermissions)
            ->filter(fn (mixed $permission): bool => is_string($permission) && $permission !== '')
            ->unique()
            ->values();

        $allowed = $approved->merge($historical)->unique();
        $unknown = $requested->diff($allowed);

        if ($unknown->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selectedPermissions' => 'Có quyền không hợp lệ hoặc không còn thuộc catalog đang được hệ thống cho phép.',
            ]);
        }

        $persistedPermissionNames = Permission::query()
            ->where('guard_name', self::ADMIN_GUARD)
            ->whereIn('name', $requested)
            ->pluck('name');

        if ($requested->diff($persistedPermissionNames)->isNotEmpty()) {
            throw ValidationException::withMessages([
                'selectedPermissions' => 'Có quyền chưa được đồng bộ vào hệ thống permissions.',
            ]);
        }

        $saved = DB::transaction(function () use ($role, $name, $requested): Role {
            $target = $role ?? new Role();
            $target->name = $name;
            $target->guard_name = self::ADMIN_GUARD;
            $target->save();
            $target->syncPermissions($requested->all());

            return $target->fresh('permissions');
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $saved;
    }

    public function delete(int $id): string
    {
        return DB::transaction(function () use ($id): string {
            $role = $this->queryRoles()->findOrFail($id);

            if ($role->name === self::PROTECTED_ROLE) {
                return 'protected';
            }

            if ($role->users_count > 0) {
                return 'in_use';
            }

            $role->delete();

            return 'deleted';
        });
    }

    public function deleteMany(array $ids): array
    {
        return DB::transaction(function () use ($ids): array {
            $roles = Role::query()
                ->withCount('users')
                ->where('guard_name', self::ADMIN_GUARD)
                ->whereIn('id', $ids)
                ->lockForUpdate()
                ->get();

            $deleted = 0;
            $blocked = 0;

            foreach ($roles as $role) {
                if ($role->name === self::PROTECTED_ROLE || $role->users_count > 0) {
                    $blocked++;
                    continue;
                }

                $role->delete();
                $deleted++;
            }

            return ['deleted' => $deleted, 'blocked' => $blocked];
        });
    }
}
