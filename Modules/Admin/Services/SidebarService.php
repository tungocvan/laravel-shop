<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\Cache;
use Modules\Admin\Models\AdminMenu;
use Modules\Admin\Support\AdminLayoutManager;

class SidebarService
{
    protected string $cacheKey = 'admin.menus';

    public function getMenus(): array
    {
        $ttl = app(AdminLayoutManager::class)->config()['navigation']['cache_ttl'] ?? 3600;

        return Cache::remember($this->cacheKey, (int) $ttl, function (): array {
            $menus = AdminMenu::query()
                ->select([
                    'id',
                    'name',
                    'url',
                    'icon',
                    'parent_id',
                    'sort_order',
                    'can',
                    'is_active',
                ])
                ->where('is_active', true)
                ->whereNull('parent_id')
                ->with(['children' => function ($query) {
                    $query->select([
                            'id',
                            'name',
                            'url',
                            'icon',
                            'parent_id',
                            'sort_order',
                            'can',
                            'is_active',
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
                    ->filter(fn (array $child) => $this->canAccess($child, $user))
                    ->map(fn (array $child) => $this->toNavigationItem(
                        $this->withActiveState($child, $currentPath)
                    ))
                    ->values()
                    ->all();

                $menu = $this->withActiveState($menu, $currentPath, $children);
                $hasChildren = $children !== [];

                if (! $this->canAccess($menu, $user) && ! $hasChildren) {
                    return null;
                }

                return $hasChildren
                    ? $this->toNavigationGroup($menu, $children)
                    : $this->toNavigationItem($menu);
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function normalizeUrl($url): ?string
    {
        if (empty($url)) {
            return null;
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return '/' . ltrim($url, '/');
    }

    protected function buildTree($menus): array
    {
        return $menus->map(function ($menu) {
            $children = $menu->children ?? collect();

            return [
                'id' => $menu->id,
                'name' => $menu->name,
                'url' => $this->normalizeUrl($menu->url),
                'icon' => $menu->icon,
                'can' => $menu->can,
                'children' => $children->map(function ($child) {
                    return [
                        'id' => $child->id,
                        'name' => $child->name,
                        'url' => $this->normalizeUrl($child->url),
                        'icon' => $child->icon,
                        'can' => $child->can,
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
            $active = collect($children)->contains(
                fn (array $child) => (bool) ($child['active'] ?? false)
            );
        }

        $item['active'] = $active;

        return $item;
    }

    protected function toNavigationItem(array $item): array
    {
        return [
            'kind' => 'item',
            'id' => $item['id'],
            'name' => $item['name'],
            'icon' => $item['icon'] ?? null,
            'href' => $this->href($item['url'] ?? null),
            'active' => (bool) ($item['active'] ?? false),
        ];
    }

    protected function toNavigationGroup(array $item, array $children): array
    {
        return [
            'kind' => 'group',
            'id' => $item['id'],
            'name' => $item['name'],
            'icon' => $item['icon'] ?? null,
            'href' => $this->href($item['url'] ?? null),
            'active' => (bool) ($item['active'] ?? false),
            'group_id' => 'admin-nav-group-' . $item['id'],
            'children' => $children,
        ];
    }

    protected function href(?string $url): string
    {
        if (empty($url)) {
            return '#';
        }

        if (filter_var($url, FILTER_VALIDATE_URL)) {
            return $url;
        }

        return url($url);
    }

    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }
}
