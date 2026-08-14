<?php

namespace Modules\User\database\seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserAdminSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $name = (string) config('seed.admin.name', 'Từ Ngọc Vân');
        $email = (string) config('seed.admin.email', 'tungocvan@gmail.com');
        $configuredPassword = config('seed.admin.password');
        $password = is_string($configuredPassword) && $configuredPassword !== ''
            ? $configuredPassword
            : (app()->isProduction() ? null : '12345678');

        if ($password === null) {
            throw new RuntimeException('SEED_ADMIN_PASSWORD is required when running UserAdminSeeder in production.');
        }

        $attributes = [
            'name' => $name,
            'password' => Hash::make($password),
        ];

        if (Schema::hasColumn('users', 'account_type')) {
            $attributes['account_type'] = 'system';
        }

        if (Schema::hasColumn('users', 'is_active')) {
            $attributes['is_active'] = true;
        }

        $userAdmin = User::query()->updateOrCreate(
            ['email' => $email],
            $attributes
        );

        $role = Role::findOrCreate('Super Admin', 'admin');
        $userAdmin->syncRoles([$role]);

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
