<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
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

        $this->seedEnabledModules();
        $this->seedWebsiteDemoIfAvailable();
    }

    private function seedEnabledModules(): void
    {
        foreach (config('modules.registry', []) as $name => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $manifestPath = collect([
                $module['path'].'/config/module.php',
                $module['path'].'/Config/module.php',
            ])->first(fn (string $path): bool => File::exists($path));
            $manifest = $manifestPath ? require $manifestPath : [];

            foreach ($manifest['seeders'] ?? [] as $seeder) {
                if (! is_string($seeder) || ! class_exists($seeder)) {
                    throw new \RuntimeException("Seeder [{$seeder}] của module [{$name}] không tồn tại.");
                }

                $this->call($seeder);
            }
        }
    }

    private function seedWebsiteDemoIfAvailable(): void
    {
        $websitePath = base_path('Modules/Website');
        $websiteSeeder = 'Modules\\Website\\database\\Seeders\\WebsiteDatabaseSeeder';

        if (! File::isDirectory($websitePath)) {
            return;
        }

        if (! class_exists($websiteSeeder)) {
            throw new \RuntimeException(
                "Module Website tồn tại nhưng seeder [{$websiteSeeder}] không load được."
            );
        }

        $this->call($websiteSeeder);
    }
}
