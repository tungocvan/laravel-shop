<?php

namespace Modules\System\Services;

use Illuminate\Routing\Route as RouteObject;
use Illuminate\Support\Facades\Route;

class AdminLoginRedirectService
{
    public const SETTING_KEY = 'admin_login_redirect_route';
    public const DEFAULT_ROUTE = 'admin.dashboard';
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
            ->sortBy(fn (string $label, string $name): int => $name === self::DEFAULT_ROUTE ? 0 : 1)
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
        if ($this->isAllowedRoute(self::FALLBACK_ADMIN_ROUTE)) {
            return self::FALLBACK_ADMIN_ROUTE;
        }

        $first = array_key_first($this->availableRoutes());

        return is_string($first) ? $first : self::FALLBACK_ADMIN_ROUTE;
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
