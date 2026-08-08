<?php

namespace Modules\Admin\Services;

use Modules\Admin\Models\AdminMenu;
use Illuminate\Support\Facades\Cache;
use Modules\Admin\Support\AdminLayoutManager;

class SidebarService
{
    protected string $cacheKey = 'admin.menus';

    // ======================
    // GET MENUS
    // ======================
    public function getMenus()
    {
        $ttl = app(AdminLayoutManager::class)->config()['navigation']['cache_ttl'] ?? 3600;

        return Cache::remember($this->cacheKey, (int) $ttl, function () {

            $menus = AdminMenu::query()
                ->select([
                    'id',
                    'name',
                    'url',
                    'icon',
                    'parent_id',
                    'sort_order',
                    'can',
                    'is_active'
                ])
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($q) {
                    $q->select([
                            'id',
                            'name',
                            'url',
                            'icon',
                            'parent_id',
                            'sort_order',
                            'can',
                            'is_active'
                        ])
                        ->where('is_active', true)
                        ->orderBy('sort_order');
                }])
                ->orderBy('sort_order')
                ->get();

            return $this->buildTree($menus);
        });
    }

    public function getMenusForUser($user, ?string $currentPath = null): array
    {
        $currentPath = trim($currentPath ?? request()->path(), '/');

        return collect($this->getMenus())
            ->map(function (array $menu) use ($user, $currentPath) {
                $children = collect($menu['children'] ?? [])
                    ->filter(fn(array $child) => $this->canAccess($child, $user))
                    ->map(fn(array $child) => $this->withActiveState($child, $currentPath))
                    ->values()
                    ->all();

                $menu = $this->withActiveState($menu, $currentPath, $children);
                $menu['children'] = $children;
                $menu['has_children'] = ! empty($children);

                if (! $this->canAccess($menu, $user) && ! $menu['has_children']) {
                    return null;
                }

                return $menu;
            })
            ->filter()
            ->values()
            ->all();
    }

    // ======================
    // SAFE URL NORMALIZER
    // ======================
    protected function normalizeUrl($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        // nếu đã là full URL thì giữ nguyên
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        // đảm bảo bắt đầu bằng /
        return '/' . ltrim($url, '/');
    }

    // ======================
    // BUILD PRE-PROCESSED TREE (ULTRA SAFE)
    // ======================
    protected function buildTree($menus): array
    {
        return $menus->map(function ($menu) {

            $children = $menu->children ?? collect();

            return [
                'id'   => $menu->id,
                'name' => $menu->name,

                // ✅ FIX QUAN TRỌNG: luôn là STRING hoặc NULL
                'url'  => $this->normalizeUrl($menu->url),

                'icon' => $menu->icon,
                'can'  => $menu->can,

                'has_children' => $children->isNotEmpty(),

                'children' => $children->map(function ($child) {
                    return [
                        'id'   => $child->id,
                        'name' => $child->name,

                        // ✅ FIX CHILD URL
                        'url'  => $this->normalizeUrl($child->url),

                        'icon' => $child->icon,
                        'can'  => $child->can,
                    ];
                })->values()->all(),
            ];
        })->values()->all();
    }

    protected function canAccess(array $item, $user): bool
    {
        if (empty($item['can'])) {
            return true;
        }

        return $user && method_exists($user, 'can') && $user->can($item['can']);
    }

    protected function withActiveState(array $item, string $currentPath, array $children = []): array
    {
        $pattern = trim($item['url'] ?? '', '/');

        $active = false;

        if ($pattern !== '') {
            $active = $currentPath === $pattern;

            if (! $active && $pattern !== 'admin') {
                $active = str_starts_with($currentPath, $pattern . '/');
            }
        }

        if (! $active && $children !== []) {
            $active = collect($children)->contains(fn(array $child) => (bool) ($child['active'] ?? false));
        }

        $item['active'] = $active;

        return $item;
    }

    // ======================
    // CLEAR CACHE
    // ======================
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
