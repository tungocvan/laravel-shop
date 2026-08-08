<?php

namespace Modules\User\Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;
use Illuminate\Support\Facades\Schema;

class UserAdminSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $attributes = [
            'name' => 'Từ Ngọc Vân',
            'password' => bcrypt('123456'),
        ];

        // Các cột này thuộc module Account/Identity, không thuộc schema lõi User.
        if (Schema::hasColumn('users', 'account_type')) {
            $attributes['account_type'] = 'system';
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $attributes['is_active'] = true;
        }

        $userAdmin = User::query()->firstOrCreate(
            ['email' => 'tungocvan@gmail.com'],
            $attributes
        );

        $role = Role::findByName('Super Admin', 'admin');

        $userAdmin->assignRole($role);

        app()[PermissionRegistrar::class]->forgetCachedPermissions();
    }
}
