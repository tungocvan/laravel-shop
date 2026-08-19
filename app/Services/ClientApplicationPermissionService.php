<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ClientApplicationPermissionService
{
    public function __construct(private readonly ClientApplicationRegistry $registry)
    {
    }

    public function definitions(): Collection
    {
        return $this->registry->all()->flatMap(function (array $application): array {
            $rows = [];

            if (! empty($application['permission'])) {
                $rows[] = [
                    'application' => $application['key'],
                    'feature' => null,
                    'name' => $application['permission'],
                    'label' => 'Truy cập '.$application['name'],
                ];
            }

            foreach ($application['features'] as $feature) {
                if (empty($feature['permission'])) {
                    continue;
                }

                $rows[] = [
                    'application' => $application['key'],
                    'feature' => $feature['key'],
                    'name' => $feature['permission'],
                    'label' => $feature['name'],
                ];
            }

            return $rows;
        })->unique('name')->values();
    }

    public function sync(): int
    {
        $created = 0;

        foreach ($this->definitions() as $definition) {
            $permission = Permission::query()->firstOrCreate([
                'name' => $definition['name'],
                'guard_name' => 'web',
            ]);

            if ($permission->wasRecentlyCreated) {
                $created++;
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $created;
    }

    public function syncUser(User $user, array $selected): void
    {
        $allowed = $this->definitions()->pluck('name')->all();
        $selected = array_values(array_intersect($allowed, $selected));

        $currentClientPermissions = $user->permissions
            ->where('guard_name', 'web')
            ->pluck('name')
            ->filter(fn (string $name): bool => str_starts_with($name, 'client.'))
            ->all();

        foreach ($currentClientPermissions as $permission) {
            $user->revokePermissionTo($permission);
        }

        foreach ($selected as $permission) {
            $user->givePermissionTo($permission);
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncRole(Role $role, array $selected): void
    {
        abort_unless($role->guard_name === 'web', 422, 'Chỉ role guard web mới được gán quyền Client.');

        $allowed = $this->definitions()->pluck('name')->all();
        $selected = array_values(array_intersect($allowed, $selected));

        $nonClientPermissions = $role->permissions
            ->reject(fn (Permission $permission): bool => $permission->guard_name === 'web'
                && str_starts_with($permission->name, 'client.'))
            ->pluck('name')
            ->all();

        $role->syncPermissions(array_merge($nonClientPermissions, $selected));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncSuperAdminUsers(): int
    {
        $permissions = $this->definitions()->pluck('name')->all();
        $users = User::query()
            ->whereHas('roles', fn ($query) => $query
                ->where('name', 'Super Admin')
                ->where('guard_name', 'admin'))
            ->get();

        foreach ($users as $user) {
            foreach ($permissions as $permission) {
                if (! $user->hasDirectPermission($permission, 'web')) {
                    $user->givePermissionTo(Permission::findByName($permission, 'web'));
                }
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $users->count();
    }
}
