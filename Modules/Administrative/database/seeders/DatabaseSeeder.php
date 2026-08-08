<?php

namespace Modules\Administrative\database\seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ProcedureSeeder::class,
            AdministrativeMenuSeeder::class,
        ]);
    }
}
