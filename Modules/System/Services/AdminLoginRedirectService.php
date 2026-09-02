<?php

namespace Modules\System\Services;

use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;

class AdminLoginRedirectService
{
    public const SETTING_KEY = 'admin_login_redirect_route';
    public const DEFAULT_ROUTE = 'home';
    public const FALLBACK_ADMIN_ROUTE = 'admin.dashboard';

    public function __construct(private readonly SettingsService $settings)
    {
    }

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
                $label = $uri === '/'
                    ? 'Trang gốc — /'
                    : $name.' — '.$uri;

                return [$name => $label];
            })
            ->sortBy(fn (string $label, string $name): int => $name === $this->rootRouteName() ? 0 : 1)
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
        $rootRoute = $this->rootRouteName();

        if ($rootRoute !== null && $this->isAllowedRoute($rootRoute)) {
            return $rootRoute;
        }

        if ($this->isAllowedRoute(self::FALLBACK_ADMIN_ROUTE)) {
            return self::FALLBACK_ADMIN_ROUTE;
        }

        $first = array_key_first($this->availableRoutes());

        return is_string($first) ? $first : self::FALLBACK_ADMIN_ROUTE;
    }

    private function rootRouteName(): ?string
    {
        foreach (Route::getRoutes()->getRoutes() as $route) {
            if ($route instanceof RouteObject
                && $route->uri() === '/'
                && in_array('GET', $route->methods(), true)
                && $route->getName()) {
                return (string) $route->getName();
            }
        }

        return null;
    }

    private function isSelectableRoute(RouteObject $route): bool
    {
        $name = (string) $route->getName();

        if ($name === '' || ! in_array('GET', $route->methods(), true) || str_contains($route->uri(), '{')) {
            return false;
        }

        return str_starts_with($name, 'admin.') || $route->uri() === '/';
    }
}
