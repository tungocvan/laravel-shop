<?php

namespace Modules\Role\database\Seeders;

use App\Modules\ModulePermissionManager;
use Illuminate\Database\Seeder;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        app(ModulePermissionManager::class)->syncAllActiveToSuperAdmin();
    }
}
