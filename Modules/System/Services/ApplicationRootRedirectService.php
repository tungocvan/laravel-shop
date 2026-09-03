<?php

namespace Modules\System\Services;

use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;

class ApplicationRootRedirectService
{
    public const SETTING_KEY = 'application_root_fallback_route';

    public const DEFAULT_ROUTE = 'admin.entry';

    public const FALLBACK_ROUTE = 'admin.dashboard';

    public function __construct(private readonly SettingsService $settings) {}

    public function configuredRoute(): string
    {
        $route = trim((string) $this->settings->get(self::SETTING_KEY, self::DEFAULT_ROUTE));

        return $this->isAllowedRoute($route) ? $route : $this->fallbackRoute();
    }

    public function availableRoutes(): array
    {
        return collect(Route::getRoutes()->getRoutes())
            ->filter(fn (RouteObject $route): bool => $this->isSelectableRoute($route))
            ->mapWithKeys(function (RouteObject $route): array {
                $name = (string) $route->getName();
                $uri = '/'.ltrim($route->uri(), '/');
                $scope = $this->routeScope($route);

                return [$name => $name.' — '.$uri.' · '.$scope];
            })
            ->sortBy(function (string $label, string $name): array {
                return [
                    $name === self::DEFAULT_ROUTE ? 0 : 1,
                    $label,
                ];
            })
            ->all();
    }

    public function isAllowedRoute(string $name): bool
    {
        if ($name === '' || ! Route::has($name)) {
            return false;
        }

        $route = Route::getRoutes()->getByName($name);

        return $route instanceof RouteObject && $this->isSelectableRoute($route);
    }

    public function fallbackRoute(): string
    {
        if ($this->isAllowedRoute(self::DEFAULT_ROUTE)) {
            return self::DEFAULT_ROUTE;
        }

        if ($this->isAllowedRoute(self::FALLBACK_ROUTE)) {
            return self::FALLBACK_ROUTE;
        }

        $first = array_key_first($this->availableRoutes());

        return is_string($first) ? $first : self::FALLBACK_ROUTE;
    }

    private function isSelectableRoute(RouteObject $route): bool
    {
        $name = trim((string) $route->getName());
        $uri = '/'.ltrim($route->uri(), '/');

        if (
            $name === ''
            || $route->isFallback
            || ! in_array('GET', $route->methods(), true)
            || str_contains($route->uri(), '{')
            || $uri === '/'
        ) {
            return false;
        }

        return true;
    }

    private function routeScope(RouteObject $route): string
    {
        $middleware = collect($route->gatherMiddleware())->map(static fn ($item): string => (string) $item);

        if ($middleware->contains('auth:admin')) {
            return 'Yêu cầu Admin';
        }

        if ($middleware->contains('auth:web') || $middleware->contains('auth')) {
            return 'Yêu cầu đăng nhập';
        }

        return 'Công khai';
    }
}
