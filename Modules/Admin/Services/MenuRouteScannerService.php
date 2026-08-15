<?php

namespace Modules\Admin\Services;

use Illuminate\Routing\Route;
use Illuminate\Routing\Router;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;

class MenuRouteScannerService
{
    public function __construct(
        private readonly Router $router,
        private readonly MenuService $menuService,
    ) {
    }

    public function candidates(): array
    {
        $existingUrls = AdminMenu::menu()
            ->whereNotNull('url')
            ->pluck('url')
            ->map(fn ($url): string => $this->normalizeUrl((string) $url))
            ->filter()
            ->values()
            ->all();

        $existingSlugs = AdminMenu::menu()->whereNotNull('slug')->pluck('slug')->map(fn ($slug): string => (string) $slug)->all();
        $candidates = [];

        foreach ($this->router->getRoutes() as $route) {
            if (! $route instanceof Route || ! $this->eligible($route)) {
                continue;
            }

            $routeName = (string) $route->getName();
            $url = '/'.ltrim($route->uri(), '/');
            $normalizedUrl = $this->normalizeUrl($url);

            if (in_array($routeName, $existingSlugs, true) || in_array($normalizedUrl, $existingUrls, true)) {
                continue;
            }

            $group = $this->groupFor($routeName, $route->uri());
            $candidates[] = [
                'id' => sha1($routeName.'|'.$normalizedUrl),
                'route_name' => $routeName,
                'name' => $this->displayName($routeName, $route->uri()),
                'url' => $url,
                'permission' => $this->permissionFromMiddleware($route),
                'group' => $group,
                'methods' => array_values(array_intersect($route->methods(), ['GET', 'HEAD'])),
            ];
        }

        usort($candidates, fn (array $a, array $b): int => [$a['group'], $a['name']] <=> [$b['group'], $b['name']]);

        return $candidates;
    }

    public function persistSelected(array $candidateIds): int
    {
        $candidateIds = array_values(array_unique(array_filter(array_map('strval', $candidateIds))));
        if ($candidateIds === []) {
            return 0;
        }

        $approved = collect($this->candidates())
            ->filter(fn (array $candidate): bool => in_array($candidate['id'], $candidateIds, true))
            ->values()
            ->all();

        return $this->menuService->createScannedMenus($approved);
    }

    private function eligible(Route $route): bool
    {
        $methods = $route->methods();
        if (! in_array('GET', $methods, true)) {
            return false;
        }

        $name = (string) ($route->getName() ?? '');
        $uri = ltrim($route->uri(), '/');

        if ($name === '' || (! str_starts_with($name, 'admin.') && ! str_starts_with($uri, 'admin/'))) {
            return false;
        }

        if (str_contains($uri, '{')) {
            return false;
        }

        $middleware = array_values(array_filter($route->gatherMiddleware(), 'is_string'));
        if (! in_array('auth:admin', $middleware, true)) {
            return false;
        }

        $blocked = [
            'livewire', 'debugbar', 'telescope', 'horizon', 'ignition', 'storage',
            'sanctum', 'health', 'up', 'broadcasting/auth', 'csrf-cookie',
            'login', 'logout', 'password', 'forgot', 'reset-password',
        ];

        $haystack = strtolower($name.' '.$uri);
        foreach ($blocked as $needle) {
            if (str_contains($haystack, $needle)) {
                return false;
            }
        }

        return true;
    }

    private function groupFor(string $routeName, string $uri): string
    {
        if (str_starts_with($routeName, 'admin.')) {
            $segments = explode('.', substr($routeName, 6));
            $first = $segments[0] ?? 'admin';

            return in_array($first, ['menus', 'dashboard'], true) ? 'admin' : $first;
        }

        $segments = array_values(array_filter(explode('/', trim($uri, '/'))));

        return $segments[1] ?? 'admin';
    }

    private function displayName(string $routeName, string $uri): string
    {
        $segments = explode('.', $routeName);
        $tail = array_values(array_filter(array_slice($segments, 1), fn (string $segment): bool => ! in_array($segment, ['index', 'show', 'create', 'edit'], true)));
        $source = end($tail) ?: basename(trim($uri, '/')) ?: 'Menu';

        return Str::headline(str_replace(['-', '_'], ' ', (string) $source));
    }

    private function permissionFromMiddleware(Route $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (! is_string($middleware) || ! str_starts_with($middleware, 'permission:')) {
                continue;
            }

            $value = substr($middleware, strlen('permission:'));
            $permission = trim(explode(',', $value, 2)[0] ?? '');

            return $permission !== '' ? $permission : null;
        }

        return null;
    }

    private function normalizeUrl(string $url): string
    {
        $url = '/'.ltrim(trim($url), '/');

        return rtrim($url, '/') ?: '/';
    }
}
