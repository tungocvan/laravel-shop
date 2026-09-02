<?php

namespace Modules\Role\Services;

use App\Modules\ModulePermissionManager;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionCatalogService
{
    public function __construct(
        private readonly ModulePermissionManager $modulePermissions,
    ) {}

    public function activeGroups(): array
    {
        return $this->modulePermissions->activeGroups();
    }

    public function previewActiveSync(): array
    {
        return $this->modulePermissions->previewActiveSync();
    }

    public function syncAllActiveToSuperAdmin(): array
    {
        return $this->modulePermissions->syncAllActiveToSuperAdmin();
    }

    public function createDeclaredPermissions(string $moduleName, array $actions): array
    {
        $module = Str::lower($moduleName);
        $group = collect($this->activeGroups())
            ->first(fn (array $permissions, string $name): bool => Str::lower($name) === $module);

        if (! is_array($group)) {
            return ['ok' => false, 'reason' => 'module_not_found', 'created' => 0, 'module' => $module];
        }

        $requested = collect($actions)
            ->filter()
            ->keys()
            ->map(fn (string $action): string => $action.'_'.$module)
            ->values();
        $approved = $requested->intersect($group)->values();

        if ($approved->count() !== $requested->count()) {
            return ['ok' => false, 'reason' => 'permission_not_declared', 'created' => 0, 'module' => $module];
        }

        $created = 0;

        DB::transaction(function () use ($approved, &$created): void {
            foreach ($approved as $permissionName) {
                $permission = Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => RoleService::ADMIN_GUARD,
                ]);

                if ($permission->wasRecentlyCreated) {
                    $created++;
                }
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();

        return ['ok' => true, 'reason' => null, 'created' => $created, 'module' => $module];
    }
}
