<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\Admin\database\seeders\AdminMenuSeeder;
use Modules\Request\Database\Seeders\RequestDemoSeeder;
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
        $seeders = [
            RolesAndPermissionsSeeder::class,
            UserAdminSeeder::class,
            AdminMenuSeeder::class,
        ];

        if (config('request.settings.demo_seeders_enabled', false) === true) {
            $seeders[] = RequestDemoSeeder::class;
        }

        $this->call($seeders);
    }
}
