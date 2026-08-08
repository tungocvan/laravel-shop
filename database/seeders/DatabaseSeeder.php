<?php

namespace Database\Seeders;

//use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Modules\Role\database\seeders\RolesAndPermissionsSeeder;
use Modules\User\database\seeders\UserAdminSeeder;
// use Modules\User\database\seeders\UserSeeder;
use Modules\Admin\database\seeders\AdminMenuSeeder;
//use Modules\Website\database\Seeders\WebsiteDatabaseSeeder;

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
    }

    private function seedEnabledModules(): void
    {
        foreach (config('modules.registry', []) as $name => $module) {
            if (! ($module['enabled'] ?? false)) {
                continue;
            }

            $manifestPath = collect([
                $module['path'] . '/config/module.php',
                $module['path'] . '/Config/module.php',
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
}
