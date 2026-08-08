<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;

class PermissionDeleteModule extends Command
{
    protected $signature = 'permission:delete-module {name} {--force}';

    protected $description = 'Delete permissions of a module';

    public function handle()
    {
        $name = strtolower($this->argument('name'));

        if (!$this->option('force')) {
            $this->error('❌ Use --force to delete permissions');
            return self::INVALID;
        }

        $permissions = [
            "view_{$name}",
            "create_{$name}",
            "edit_{$name}",
            "delete_{$name}",
        ];

        foreach ($permissions as $perm) {

            $permission = Permission::where('name', $perm)->first();

            if ($permission) {
                $permission->delete();
                $this->info("🗑️ Deleted: {$perm}");
            } else {
                $this->warn("⚠️ Not found: {$perm}");
            }
        }

        return self::SUCCESS;
    }
}
