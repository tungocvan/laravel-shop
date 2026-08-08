<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

class PermissionModule extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'permission:module {name}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'permission for module';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $name = strtolower($this->argument('name'));

        $permissionsArray = [
            "view_{$name}",
            "create_{$name}",
            "edit_{$name}",
            "delete_{$name}",
        ];

        foreach ($permissionsArray as $perm) {

            if (! Permission::where('name', $perm)->where('guard_name', 'admin')->exists()) {
                Permission::findOrCreate($perm, 'admin');
                $this->info("✅ Created permission: {$perm}");
            } else {
                $this->warn("⚠️ Already exists: {$perm}");
            }
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
