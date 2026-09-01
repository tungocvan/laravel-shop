<?php

namespace Modules\Admission\database\seeders;

use Illuminate\Database\Seeder;

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
