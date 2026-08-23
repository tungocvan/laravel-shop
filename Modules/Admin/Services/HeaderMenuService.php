<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\HeaderMenu;
use Modules\Admin\Models\HeaderMenuItem;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class HeaderMenuService
{
    public function getMenuTreeByLocation(string $location): Collection
    {
        return Cache::remember("menu_tree_{$location}", 3600, function () use ($location) {
            $menu = HeaderMenu::where('location', $location)->where('is_active', true)->first();

            if (! $menu) {
                return new Collection();
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

    public function exportAdminConfigItems(): array
    {
        return $this->getMenuTreeByLocation('admin')
            ->map(function ($item, int $index) {
                $url = is_string($item->url) ? trim($item->url) : '';

                if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//')) {
                    return null;
                }

                return [
                    'enabled' => true,
                    'label' => mb_substr((string) $item->title, 0, 80),
                    'icon' => $this->configIcon($item->icon),
                    'url' => mb_substr($url, 0, 255),
                    'target' => $item->target === '_blank' ? '_blank' : '_self',
                    'permission' => null,
                    'order' => (int) ($item->sort_order ?? $index),
                ];
            })
            ->filter()
            ->values()
            ->all();
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

    public function reorderItems(array $items): void
    {
        DB::transaction(function () use ($items) {
            foreach ($items as $item) {
                HeaderMenuItem::where('id', $item['id'])->update(['sort_order' => $item['sort_order']]);
            }
        });

        Cache::flush();
    }

    protected function clearMenuCache($menuId): void
    {
        $menu = HeaderMenu::find($menuId);
        if ($menu) {
            Cache::forget("menu_tree_{$menu->location}");
        }
    }

    private function configIcon(mixed $icon): string
    {
        $icon = is_string($icon) ? strtolower($icon) : '';

        return match (true) {
            str_contains($icon, 'user') => 'user',
            str_contains($icon, 'gear'), str_contains($icon, 'cog') => 'gear',
            str_contains($icon, 'lock') => 'lock',
            str_contains($icon, 'key') => 'key',
            str_contains($icon, 'shield') => 'shield',
            default => 'link',
        };
    }
}
