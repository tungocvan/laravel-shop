<?php

namespace Modules\Website\Services;

use Modules\Website\Models\HeaderMenu;
use Modules\Website\Models\HeaderMenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HeaderMenuService
{
    /**
     * Lấy cấu trúc menu theo vị trí để hiển thị Frontend
     * Có Cache, Eager Load con cháu
     */
    public function getMenuTreeByLocation(string $location): Collection
    {
        return Cache::remember("menu_tree_{$location}", 3600, function () use ($location) {
            $menu = HeaderMenu::where('location', $location)->where('is_active', true)->first();

            if (!$menu) return new Collection();

            // Lấy root items và load đệ quy children
            return $menu->rootItems()
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        });
    }

    /**
     * Tạo mới Menu Item
     */
    public function createItem(array $data): HeaderMenuItem
    {
        $item = HeaderMenuItem::create($data);
        $this->clearMenuCache($item->header_menu_id);
        return $item;
    }

    /**
     * Cập nhật Menu Item
     */
    public function updateItem(int $id, array $data): bool
    {
        $item = HeaderMenuItem::findOrFail($id);
        $updated = $item->update($data);

        if ($updated) {
            $this->clearMenuCache($item->header_menu_id);
        }
        return $updated;
    }

    /**
     * Xóa Menu Item (và con của nó - nhờ cascade delete DB)
     */
    public function deleteItem(int $id): bool
    {
        $item = HeaderMenuItem::findOrFail($id);
        $menuId = $item->header_menu_id;

        $item->delete();
        $this->clearMenuCache($menuId);

        return true;
    }

    /**
     * Sắp xếp lại thứ tự (Sort Order)
     * Input: [{id: 1, sort_order: 0}, {id: 5, sort_order: 1}]
     */
    public function reorderItems(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                HeaderMenuItem::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        // Xóa cache toàn bộ menu để an toàn
        Cache::flush();
    }

    protected function clearMenuCache($menuId)
    {
        $menu = HeaderMenu::find($menuId);
        if ($menu) {
            Cache::forget("menu_tree_{$menu->location}");
        }
    }
}
