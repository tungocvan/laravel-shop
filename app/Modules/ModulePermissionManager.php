<?php

namespace App\Modules;

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
