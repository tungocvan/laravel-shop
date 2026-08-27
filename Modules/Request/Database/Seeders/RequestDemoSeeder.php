<?php

namespace Modules\Request\Database\Seeders;

use Illuminate\Database\Seeder;

class RequestDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (config('request.settings.demo_seeders_enabled', false) !== true) {
            $this->command?->warn('RequestDemoSeeder skipped: demo seeders are disabled for this environment.');

            return;
        }

        $this->command?->info('RequestDemoSeeder enabled: seeding Request starter templates.');

        $this->call([
            RequestStarterTemplateSeeder::class,
            RequestOffboardingHandoverSeeder::class,
        ]);
    }
}
