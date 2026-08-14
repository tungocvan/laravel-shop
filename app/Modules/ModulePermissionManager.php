<?php

namespace App\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionManager
{
    public function sync(array $module): int
    {
        $permissions = $this->permissionsFromPath($module['path']);

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'admin');
        }

        $superAdmin = Role::findOrCreate('Super Admin', 'admin');
        $superAdmin->givePermissionTo($permissions);
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return count($permissions);
    }

    public function activeGroups(): array
    {
        return collect(config('modules.registry', []))
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->mapWithKeys(function (array $module, string $name): array {
                $permissions = $this->permissionsFromPath($module['path']);

                return $permissions === [] ? [] : [$name => $permissions];
            })
            ->all();
    }

    public function previewActiveSync(): array
    {
        $groups = $this->activeGroups();
        $permissions = collect($groups)->flatten()->unique()->sort()->values();
        $existing = Permission::query()
            ->where('guard_name', 'admin')
            ->whereIn('name', $permissions)
            ->pluck('name');
        $missing = $permissions->diff($existing)->values();
        $superAdmin = Role::query()
            ->where('name', 'Super Admin')
            ->where('guard_name', 'admin')
            ->first();
        $assigned = $superAdmin
            ? $superAdmin->permissions->pluck('name')->intersect($permissions)
            : collect();

        return [
            'modules' => count(config('modules.registry', [])),
            'active_modules' => collect(config('modules.registry', []))->where('enabled', true)->count(),
            'modules_with_permissions' => count($groups),
            'total' => $permissions->count(),
            'existing' => $existing->count(),
            'missing' => $missing->all(),
            'missing_count' => $missing->count(),
            'super_admin_assigned' => $assigned->count(),
            'super_admin_missing' => $permissions->diff($assigned)->values()->all(),
        ];
    }

    public function syncAllActiveToSuperAdmin(): array
    {
        $before = $this->previewActiveSync();
        $groups = $this->activeGroups();
        $permissions = collect($groups)->flatten()->unique()->values();

        DB::transaction(function () use ($permissions): void {
            foreach ($permissions as $permission) {
                Permission::findOrCreate($permission, 'admin');
            }

            $superAdmin = Role::findOrCreate('Super Admin', 'admin');
            $superAdmin->givePermissionTo($permissions->all());
        });

        $this->forgetCache();
        $after = $this->previewActiveSync();

        return [
            'created' => $before['missing_count'],
            'assigned' => count($before['super_admin_missing']),
            'total' => $after['total'],
            'modules_with_permissions' => $after['modules_with_permissions'],
        ];
    }

    public function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function permissionsFromPath(string $modulePath): array
    {
        $manifest = collect([
            $modulePath . '/config/module.php',
            $modulePath . '/Config/module.php',
        ])->first(fn (string $path): bool => File::exists($path));

        if ($manifest === null) {
            return [];
        }

        $config = require $manifest;

        return collect($config['permissions'] ?? [])
            ->filter(fn (mixed $permission): bool => is_string($permission) && trim($permission) !== '')
            ->map(fn (string $permission): string => trim($permission))
            ->unique()
            ->values()
            ->all();
    }
}
