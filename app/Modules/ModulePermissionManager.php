<?php

namespace App\Modules;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ModulePermissionManager
{
    public function __construct(
        private readonly ModuleCatalog $catalog,
    ) {}

    public function sync(array $module): int
    {
        $permissionsByGuard = $this->permissionsByGuardFromPath($module['path']);

        $this->forgetCache();

        $permissionModelsByGuard = collect($permissionsByGuard)
            ->map(fn (array $permissions, string $guard) => collect($permissions)
                ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, $guard))
                ->values());

        $adminPermissions = $permissionModelsByGuard->get('admin', collect());
        if ($adminPermissions->isNotEmpty()) {
            Role::findOrCreate('Super Admin', 'admin')->givePermissionTo($adminPermissions);
        }

        $this->forgetCache();

        return $permissionModelsByGuard->sum(fn ($permissions): int => $permissions->count());
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

    public function discoverModules(): array
    {
        $registry = collect(config('modules.registry', []));
        $modules = $this->catalog->discover()
            ->sortBy(fn (array $module): string => strtolower($module['name']))
            ->values();

        return $modules->map(function (array $module) use ($registry): array {
            $registryKey = $registry->keys()->first(fn (string $name): bool => strcasecmp($name, $module['name']) === 0);
            $registered = $registryKey !== null;
            $registryModule = $registered ? (array) $registry->get($registryKey) : [];
            $permissions = $module['manifest_exists']
                ? ($this->permissionsByGuardFromManifest($module['manifest'])['admin'] ?? [])
                : [];
            $permissionsRequired = $module['permissions_required'];

            $status = 'ok';
            if (! $registered) {
                $status = 'missing_registry';
            } elseif (! $module['manifest_exists']) {
                $status = 'missing_manifest';
            } elseif (! $permissionsRequired) {
                $status = 'no_permission_required';
            } elseif ($permissions === []) {
                $status = 'missing_permissions';
            }

            return [
                'name' => $module['name'],
                'path' => $module['path'],
                'registered' => $registered,
                'registry_enabled' => $registered ? (bool) ($registryModule['enabled'] ?? false) : false,
                'manifest' => $module['manifest_exists'],
                'manifest_enabled' => $module['manifest_exists'] ? $module['default_enabled'] : false,
                'permissions_required' => $permissionsRequired,
                'permission_count' => count($permissions),
                'permissions' => $permissions,
                'status' => $status,
            ];
        })->all();
    }

    public function previewActiveSync(): array
    {
        $groups = $this->activeGroups();
        $permissions = collect($groups)->flatten()->unique()->sort()->values();
        $existing = Permission::query()->where('guard_name', 'admin')->whereIn('name', $permissions)->pluck('name');
        $missing = $permissions->diff($existing)->values();
        $superAdmin = Role::query()->where('name', 'Super Admin')->where('guard_name', 'admin')->first();
        $assigned = $superAdmin ? $superAdmin->permissions->pluck('name')->intersect($permissions) : collect();
        $discovered = collect($this->discoverModules());
        $warningStatuses = ['missing_registry', 'missing_manifest', 'missing_permissions'];

        return [
            'modules' => count(config('modules.registry', [])),
            'active_modules' => collect(config('modules.registry', []))->where('enabled', true)->count(),
            'filesystem_modules' => $discovered->count(),
            'modules_with_permissions' => count($groups),
            'modules_without_permissions' => $discovered->where('status', 'missing_permissions')->pluck('name')->values()->all(),
            'modules_without_manifest' => $discovered->where('status', 'missing_manifest')->pluck('name')->values()->all(),
            'modules_without_registry' => $discovered->where('status', 'missing_registry')->pluck('name')->values()->all(),
            'modules_without_permission_requirement' => $discovered->where('status', 'no_permission_required')->pluck('name')->values()->all(),
            'audit_warnings' => $discovered->whereIn('status', $warningStatuses)->values()->all(),
            'module_audit' => $discovered->values()->all(),
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
        $permissionsByGuard = $this->activePermissionsByGuard();

        DB::transaction(function () use ($permissionsByGuard): void {
            collect($permissionsByGuard)->each(function (array $permissions, string $guard): void {
                $permissionModels = collect($permissions)
                    ->map(fn (string $permission): Permission => Permission::findOrCreate($permission, $guard))
                    ->values();

                if ($guard === 'admin' && $permissionModels->isNotEmpty()) {
                    // Pass persisted Permission models directly. This avoids a second
                    // name lookup through Spatie's cached permission collection, which
                    // can still be stale in Docker/Redis-backed environments during a
                    // fresh seed.
                    Role::findOrCreate('Super Admin', 'admin')->givePermissionTo($permissionModels);
                }
            });
        });

        $this->forgetCache();
        $after = $this->previewActiveSync();

        return [
            'created' => $before['missing_count'],
            'assigned' => count($before['super_admin_missing']),
            'total' => $after['total'],
            'modules_with_permissions' => $after['modules_with_permissions'],
            'audit_warnings' => count($after['audit_warnings']),
        ];
    }

    public function forgetCache(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    private function manifestPath(string $modulePath): ?string
    {
        return collect([$modulePath.'/config/module.php', $modulePath.'/Config/module.php'])
            ->first(fn (string $path): bool => File::exists($path));
    }

    private function activePermissionsByGuard(): array
    {
        $permissionsByGuard = [];

        collect(config('modules.registry', []))
            ->filter(fn (array $module): bool => (bool) ($module['enabled'] ?? false))
            ->each(function (array $module) use (&$permissionsByGuard): void {
                foreach ($this->permissionsByGuardFromPath($module['path']) as $guard => $permissions) {
                    $permissionsByGuard[$guard] = array_values(array_unique([
                        ...($permissionsByGuard[$guard] ?? []),
                        ...$permissions,
                    ]));
                }
            });

        return $permissionsByGuard;
    }

    private function permissionsFromPath(string $modulePath): array
    {
        return $this->permissionsByGuardFromPath($modulePath)['admin'] ?? [];
    }

    private function permissionsByGuardFromPath(string $modulePath): array
    {
        $manifest = $this->manifestPath($modulePath);
        if ($manifest === null) {
            return [];
        }

        $config = require $manifest;

        return is_array($config) ? $this->permissionsByGuardFromManifest($config) : [];
    }

    private function permissionsByGuardFromManifest(array $config): array
    {
        $permissionsByGuard = [
            'admin' => $this->normalizePermissions($config['permissions'] ?? []),
        ];

        foreach ((array) ($config['permissions_by_guard'] ?? []) as $guard => $permissions) {
            if (! is_string($guard) || trim($guard) === '') {
                continue;
            }

            $guard = trim($guard);
            $permissionsByGuard[$guard] = array_values(array_unique([
                ...($permissionsByGuard[$guard] ?? []),
                ...$this->normalizePermissions($permissions),
            ]));
        }

        return array_filter($permissionsByGuard, fn (array $permissions): bool => $permissions !== []);
    }

    private function normalizePermissions(mixed $permissions): array
    {
        return collect(is_array($permissions) ? $permissions : [])
            ->filter(fn (mixed $permission): bool => is_string($permission) && trim($permission) !== '')
            ->map(fn (string $permission): string => trim($permission))
            ->unique()
            ->values()
            ->all();
    }
}
