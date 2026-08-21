<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Admin\database\seeders\AdminMenuSeeder;
use Modules\Role\database\seeders\RolesAndPermissionsSeeder;
use Modules\User\database\seeders\UserAdminSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            UserAdminSeeder::class,
            AdminMenuSeeder::class,
        ]);
    }
}
