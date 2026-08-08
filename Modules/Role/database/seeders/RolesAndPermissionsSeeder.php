<?php

namespace Modules\Role\database\Seeders;


use App\Modules\ModulePermissionManager;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $guardName = 'admin';

        $permissions = collect(app(ModulePermissionManager::class)->activeGroups())
            ->flatten()
            ->unique()
            ->values()
            ->all();

        foreach ($permissions as $permission) {
            Permission::firstOrCreate([
                'name' => $permission,
                'guard_name' => $guardName,
            ]);
        }

        $superAdmin = Role::firstOrCreate([
            'name' => 'Super Admin',
            'guard_name' => $guardName,
        ]);

        $superAdmin->syncPermissions(
            Permission::where('guard_name', $guardName)->get()
        );

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
