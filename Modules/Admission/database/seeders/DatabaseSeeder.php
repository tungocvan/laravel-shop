<?php

namespace Modules\Admission\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Admission\database\seeders\AdmissionLocationSeeder;
use Modules\Admission\database\seeders\AdmissionCatalogSeeder;


class DatabaseSeeder extends Seeder
{

    // php artisan db:seed --class="Modules\Admission\database\seeders\DatabaseSeeder"
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
             AdmissionLocationSeeder::class,
             AdmissionCatalogSeeder::class,
        ]);

    }
}
