<?php

namespace Modules\Admin\Services;

use Illuminate\Routing\Route as LaravelRoute;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Modules\Admin\Models\AdminMenu;
use Modules\Admin\Models\ModuleRouteTitle;

class ModuleRouteManager
{
    public function rows(): array
    {
        $savedTitles = ModuleRouteTitle::query()->pluck('title', 'route_key');

        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (LaravelRoute $route): bool => in_array('GET', $route->methods(), true))
            ->map(function (LaravelRoute $route) use ($savedTitles): ?array {
                $module = $this->moduleName($route);
                if ($module === null) {
                    return null;
                }

                $uri = $this->normalizeUri($route->uri());
                $key = sha1($module . '|' . ($route->getName() ?? '') . '|' . $uri);
                $url = $uri === '/' ? '/' : '/' . $uri;

                return [
                    'key' => $key,
                    'module' => $module,
                    'name' => $route->getName(),
                    'uri' => $uri,
                    'url' => $url,
                    'title' => $savedTitles[$key] ?? $this->suggestTitle($route, $module),
                    'permission' => $this->permission($route),
                    'in_menu' => AdminMenu::query()->whereIn('url', [$url, $uri])->exists(),
                    'is_dynamic' => str_contains($uri, '{'),
                ];
            })
            ->filter()
            ->unique(fn (array $row): string => $row['module'] . '|' . $row['uri'])
            ->sortBy(fn (array $row): string => $row['module'] . '|' . $row['uri'])
            ->values()
            ->all();
    }

    public function saveTitle(array $row, string $title): void
    {
        $title = trim($title);
        if ($title === '') {
            throw new \InvalidArgumentException('Title Module không được để trống.');
        }

        ModuleRouteTitle::query()->updateOrCreate(
            ['route_key' => $row['key']],
            [
                'module' => $row['module'],
                'route_name' => $row['name'],
                'uri' => $row['uri'],
                'title' => Str::limit($title, 255, ''),
            ]
        );
    }

    public function addMenu(array $row): AdminMenu
    {
        if ($row['is_dynamic'] ?? str_contains($row['uri'], '{')) {
            throw new \LogicException('Không thể thêm route có tham số động vào Sidebar Menu.');
        }

        if (AdminMenu::query()->whereIn('url', [$row['url'], $row['uri']])->exists()) {
            throw new \LogicException("URI {$row['url']} đã tồn tại trong menu.");
        }

        return AdminMenu::query()->create([
            'name' => $row['title'],
            'slug' => $this->uniqueSlug($row['title']),
            'url' => $row['url'],
            'can' => $row['permission'],
            'sort_order' => ((int) AdminMenu::query()->max('sort_order')) + 1,
            'is_active' => true,
        ]);
    }

    private function moduleName(LaravelRoute $route): ?string
    {
        $uses = $route->getAction('uses');
        $class = is_string($uses) ? $uses : '';

        if (preg_match('/^Modules\\\\([^\\\\]+)/', $class, $matches)) {
            return $matches[1];
        }

        if ($uses instanceof \Closure) {
            $file = (new \ReflectionFunction($uses))->getFileName() ?: '';
            if (preg_match('~/Modules/([^/]+)/~', str_replace('\\', '/', $file), $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function suggestTitle(LaravelRoute $route, string $module): string
    {
        $name = collect(explode('.', (string) $route->getName()))
            ->reject(fn (string $part): bool => in_array(Str::lower($part), ['admin', 'api', 'index', 'show'], true))
            ->implode(' ');

        if ($name === '') {
            $name = collect(explode('/', $route->uri()))
                ->reject(fn (string $part): bool => $part === 'admin' || str_starts_with($part, '{'))
                ->implode(' ');
        }

        return Str::headline($name ?: $module);
    }

    private function permission(LaravelRoute $route): ?string
    {
        foreach ($route->gatherMiddleware() as $middleware) {
            if (preg_match('/^permission:([^,|]+)/', $middleware, $matches)) {
                return $matches[1];
            }
        }

        return null;
    }

    private function normalizeUri(string $uri): string
    {
        return trim($uri, '/') ?: '/';
    }

    private function uniqueSlug(string $title): string
    {
        $base = Str::slug($title) ?: 'menu';
        $slug = $base;
        $suffix = 1;
        while (AdminMenu::query()->where('slug', $slug)->exists()) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
