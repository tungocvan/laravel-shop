<?php

namespace Modules\Website\Services;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use Modules\Website\Models\HeaderMenu;
use Modules\Website\Models\HeaderMenuItem;

class HeaderMenuService
{
    public function getAvailableLocations(): array
    {
        return (array) config('website.header.menu_locations', []);
    }

    public function getMenuTreeByLocation(string $location): Collection
    {
        return Cache::remember("menu_tree_{$location}", 3600, function () use ($location) {
            $menu = HeaderMenu::where('location', $location)->where('is_active', true)->first();

            if (! $menu) {
                return new Collection;
            }

            return $menu->rootItems()
                ->where('is_active', true)
                ->with(['children' => function ($query) {
                    $query->where('is_active', true)->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();
        });
    }

    public function getMenuForAdmin(string $location): ?HeaderMenu
    {
        $this->assertKnownLocation($location);

        return HeaderMenu::query()->where('location', $location)->first();
    }

    public function ensureMenu(string $location): HeaderMenu
    {
        $locations = $this->getAvailableLocations();
        $this->assertKnownLocation($location);

        return HeaderMenu::firstOrCreate(
            ['location' => $location],
            ['name' => (string) ($locations[$location] ?? $location), 'is_active' => true]
        );
    }

    public function createItem(array $data): HeaderMenuItem
    {
        $item = HeaderMenuItem::create($data);
        $this->clearMenuCache($item->header_menu_id);

        return $item;
    }

    public function updateItem(int $id, array $data): bool
    {
        $item = HeaderMenuItem::findOrFail($id);
        $updated = $item->update($data);

        if ($updated) {
            $this->clearMenuCache($item->header_menu_id);
        }

        return $updated;
    }

    public function deleteItem(int $id): bool
    {
        $item = HeaderMenuItem::findOrFail($id);
        $menuId = $item->header_menu_id;

        $item->delete();
        $this->clearMenuCache($menuId);

        return true;
    }

    public function moveItemByDrag(
        int $menuId,
        int $itemId,
        ?int $targetParentId,
        array $orderedIds
    ): void {
        $item = HeaderMenuItem::query()
            ->where('header_menu_id', $menuId)
            ->findOrFail($itemId);

        if ($targetParentId !== null) {
            if ($targetParentId === $itemId) {
                throw new InvalidArgumentException('Menu item không thể làm cha của chính nó.');
            }

            $parent = HeaderMenuItem::query()
                ->where('header_menu_id', $menuId)
                ->findOrFail($targetParentId);

            if ($parent->parent_id === $itemId) {
                throw new InvalidArgumentException('Không thể tạo vòng lặp menu.');
            }
        }

        DB::transaction(function () use ($menuId, $item, $targetParentId, $orderedIds) {
            $item->update(['parent_id' => $targetParentId]);

            $validIds = HeaderMenuItem::query()
                ->where('header_menu_id', $menuId)
                ->where(function ($query) use ($targetParentId) {
                    $targetParentId === null
                        ? $query->whereNull('parent_id')
                        : $query->where('parent_id', $targetParentId);
                })
                ->whereIn('id', $orderedIds)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();

            $lookup = array_flip($validIds);
            $safeIds = [];
            foreach ($orderedIds as $id) {
                $id = (int) $id;
                if (isset($lookup[$id]) && ! in_array($id, $safeIds, true)) {
                    $safeIds[] = $id;
                }
            }

            foreach ($safeIds as $index => $id) {
                HeaderMenuItem::query()
                    ->where('header_menu_id', $menuId)
                    ->whereKey($id)
                    ->update(['sort_order' => $index + 1]);
            }
        });

        $this->clearMenuCache($menuId);
    }

    public function reorderItems(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                HeaderMenuItem::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        HeaderMenu::query()->pluck('location')->each(
            fn (string $location) => Cache::forget("menu_tree_{$location}")
        );
    }

    protected function clearMenuCache($menuId): void
    {
        $menu = HeaderMenu::find($menuId);
        if ($menu) {
            Cache::forget("menu_tree_{$menu->location}");
        }
    }

    private function assertKnownLocation(string $location): void
    {
        if (! array_key_exists($location, $this->getAvailableLocations())) {
            throw new InvalidArgumentException("Unknown Header menu location [{$location}].");
        }
    }
}
