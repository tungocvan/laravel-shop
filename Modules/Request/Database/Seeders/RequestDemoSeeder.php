<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;

class RequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.demo_seeders_enabled', false) !== true) {
            return;
        }

        $this->call([
            RequestStarterTemplateSeeder::class,
        ]);
    }
}
