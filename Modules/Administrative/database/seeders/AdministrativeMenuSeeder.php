<?php

namespace Modules\Administrative\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Admin\Models\AdminMenu;

class AdministrativeMenuSeeder extends Seeder
{
    public function run(): void
    {
        $menu = AdminMenu::withTrashed()->updateOrCreate(
            ['slug' => 'ho-so-hanh-chinh'],
            [
                'name' => 'Hồ sơ hành chính',
                'url' => '/admin/administrative',
                'icon' => 'document-text',
                'can' => 'administrative.procedure.view',
                'parent_id' => null,
                'is_active' => true,
                'sort_order' => 50,
            ]
        );

        if ($menu->trashed()) {
            $menu->restore();
        }
    }
}
