<?php

namespace Modules\Admin\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;

class AdminMenuSeeder extends Seeder
{
    public function run(): void
    {
        if (AdminMenu::withTrashed()->exists()) {
            $this->command?->warn('Bỏ qua AdminMenuSeeder vì admin_menus đã có dữ liệu. Dùng chức năng Khôi phục mặc định nếu muốn ghi đè menu.');
            return;
        }

        $path = base_path('Modules/Admin/data/menus.json');
        $items = File::exists($path) ? json_decode(File::get($path), true, flags: JSON_THROW_ON_ERROR) : [];

        foreach ($items as $index => $item) {
            $this->createItem($item, null, $index);
        }

        $this->command?->info('Admin menus seeded.');
    }

    private function createItem(array $item, ?int $parentId, int $sort): void
    {
        $menu = AdminMenu::query()->create([
            'name' => $item['name'],
            'slug' => $item['slug'] ?? Str::slug($item['name']),
            'parent_id' => $parentId,
            'url' => $item['url'] ?? null,
            'icon' => $item['icon'] ?? null,
            'can' => $item['can'] ?? null,
            'sort_order' => $sort,
            'is_active' => $item['is_active'] ?? true,
        ]);

        foreach ($item['children'] ?? [] as $index => $child) {
            $this->createItem($child, (int) $menu->getKey(), $index);
        }
    }
}
