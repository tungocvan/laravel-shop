<?php

namespace Modules\Identity\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Modules\Identity\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class IdentitySeeder extends Seeder
{
    public function run(): void
    {
        $permissions = collect(config('Identity.permissions', [
            'view_identity',
            'create_identity',
            'edit_identity',
            'delete_identity',
            'import_identity',
            'export_identity',
        ]));

        $permissions->each(fn (string $permission) => Permission::findOrCreate($permission, 'admin'));

        $manager = Role::findOrCreate('Identity Manager', 'admin');
        $manager->syncPermissions($permissions->all());

        $customer = User::query()->firstOrCreate(
            ['email' => 'customer@example.test'],
            [
                'name' => 'Demo Customer',
                'password' => Hash::make('password'),
                'account_type' => 'customer',
                'is_active' => true,
            ]
        );

        $customer->customerProfile()->updateOrCreate(
            ['user_id' => $customer->id],
            ['customer_code' => 'CUS-DEMO-001', 'status' => 'active']
        );

        $employee = User::query()->firstOrCreate(
            ['email' => 'employee@example.test'],
            [
                'name' => 'Demo Employee',
                'password' => Hash::make('password'),
                'account_type' => 'employee',
                'is_active' => true,
            ]
        );

        $employee->employeeProfile()->updateOrCreate(
            ['user_id' => $employee->id],
            ['employee_code' => 'EMP-DEMO-001', 'department' => 'Operations', 'status' => 'active']
        );
    }
}
