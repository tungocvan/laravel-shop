<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class ApplicationPermissionService
{
    public function __construct(private readonly ApplicationRegistry $registry)
    {
    }

    public function definitions(): Collection
    {
        return $this->applicationDefinitions()
            ->concat($this->domainDefinitions())
            ->unique('name')
            ->values();
    }

    public function applicationDefinitions(): Collection
    {
        return $this->registry->all()->flatMap(function (array $application): array {
            $rows = [];

            if (! empty($application['permission'])) {
                $rows[] = [
                    'source' => 'application',
                    'group' => $application['key'],
                    'application' => $application['key'],
                    'feature' => null,
                    'action' => null,
                    'name' => $application['permission'],
                    'label' => 'Truy cập '.$application['name'],
                ];
            }

            foreach ($application['features'] as $feature) {
                if (! empty($feature['permission'])) {
                    $rows[] = [
                        'source' => 'application',
                        'group' => $application['key'],
                        'application' => $application['key'],
                        'feature' => $feature['key'],
                        'action' => null,
                        'name' => $feature['permission'],
                        'label' => $feature['name'],
                    ];
                }

                foreach ($feature['actions'] ?? [] as $action) {
                    if (empty($action['permission'])) {
                        continue;
                    }

                    $rows[] = [
                        'source' => 'application',
                        'group' => $application['key'],
                        'application' => $application['key'],
                        'feature' => $feature['key'],
                        'action' => $action['key'],
                        'name' => $action['permission'],
                        'label' => $feature['name'].' · '.$action['name'],
                    ];
                }
            }

            return $rows;
        })->unique('name')->values();
    }

    public function domainDefinitions(): Collection
    {
        return collect(File::directories(base_path('Modules')))
            ->flatMap(function (string $modulePath): array {
                $manifest = collect([$modulePath.'/config/module.php', $modulePath.'/Config/module.php'])
                    ->first(fn (string $path): bool => File::exists($path));
                if ($manifest === null) {
                    return [];
                }

                $config = (array) require $manifest;
                $permissions = collect((array) data_get($config, 'permissions_by_guard.web', []))
                    ->filter(fn (mixed $permission): bool => is_string($permission) && trim($permission) !== '')
                    ->map(fn (string $permission): string => trim($permission))
                    ->unique()
                    ->values();

                if ($permissions->isEmpty()) {
                    return [];
                }

                $module = (string) ($config['name'] ?? basename($modulePath));

                return $permissions->map(fn (string $permission): array => [
                    'source' => 'domain',
                    'group' => $module,
                    'application' => null,
                    'feature' => null,
                    'action' => null,
                    'name' => $permission,
                    'label' => $this->permissionLabel($permission),
                ])->all();
            })
            ->unique('name')
            ->sortBy(fn (array $definition): string => $definition['group'].'|'.$definition['name'])
            ->values();
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
        $currentManaged = $user->permissions
            ->where('guard_name', 'web')
            ->pluck('name')
            ->intersect($allowed)
            ->values()
            ->all();

        foreach ($currentManaged as $permission) {
            $user->revokePermissionTo(Permission::findByName($permission, 'web'));
        }
        foreach ($selected as $permission) {
            $user->givePermissionTo(Permission::findByName($permission, 'web'));
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncRole(Role $role, array $selected): void
    {
        abort_unless($role->guard_name === 'web', 422, 'Chỉ role guard web mới được gán quyền Web.');
        $allowed = $this->definitions()->pluck('name')->all();
        $selected = array_values(array_intersect($allowed, $selected));
        $unmanaged = $role->permissions->reject(fn (Permission $permission): bool => $permission->guard_name === 'web' && in_array($permission->name, $allowed, true))->all();
        $managed = collect($selected)->map(fn (string $name): Permission => Permission::findByName($name, 'web'))->all();
        $role->syncPermissions(array_merge($unmanaged, $managed));
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function syncSuperAdminUsers(): int
    {
        $permissionModels = $this->definitions()->pluck('name')
            ->map(fn (string $name): Permission => Permission::findByName($name, 'web'));
        $users = User::query()->whereHas('roles', fn ($query) => $query->where('name', 'Super Admin')->where('guard_name', 'admin'))->get();

        foreach ($users as $user) {
            foreach ($permissionModels as $permission) {
                if (! $user->hasDirectPermission($permission)) {
                    $user->givePermissionTo($permission);
                }
            }
        }
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return $users->count();
    }

    private function permissionLabel(string $permission): string
    {
        return collect(explode('.', $permission))
            ->map(fn (string $segment): string => str($segment)->replace('-', ' ')->headline()->toString())
            ->implode(' · ');
    }
}
