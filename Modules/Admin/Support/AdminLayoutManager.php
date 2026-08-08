<?php

namespace Modules\Admin\Support;

use Modules\Admin\Models\Setting;
use Illuminate\Support\Facades\Cache;

class AdminLayoutManager
{
    private const SETTING_KEY = 'admin_layout_config';

    private array $defaults;

    public function __construct()
    {
        $this->defaults = config('admin.admin', []);
    }

    public function config(): array
    {
        return array_replace_recursive(
            $this->defaults(),
            $this->stored()
        );
    }

    public function defaults(): array
    {
        return [
            'locale' => $this->defaults['locale'] ?? 'vi',
            'layout' => [
                'preset' => data_get($this->defaults, 'layout.preset', 'default'),
                'container' => data_get($this->defaults, 'layout.container', '7xl'),
                'density' => data_get($this->defaults, 'layout.density', 'comfortable'),
                'sticky_header' => (bool) data_get($this->defaults, 'layout.sticky_header', true),
                'show_footer' => (bool) data_get($this->defaults, 'layout.show_footer', false),
            ],
            'sidebar' => [
                'enabled' => (bool) data_get($this->defaults, 'sidebar.enabled', true),
                'desktop_collapsible' => (bool) data_get($this->defaults, 'sidebar.desktop_collapsible', true),
                'mobile_drawer' => (bool) data_get($this->defaults, 'sidebar.mobile_drawer', true),
                'persist_state' => (bool) data_get($this->defaults, 'sidebar.persist_state', true),
                'show_footer_profile' => (bool) data_get($this->defaults, 'sidebar.show_footer_profile', true),
            ],
            'header' => [
                'sticky' => (bool) data_get($this->defaults, 'header.sticky', true),
                'search' => (bool) data_get($this->defaults, 'header.search', true),
                'notifications' => (bool) data_get($this->defaults, 'header.notifications', true),
                'theme_switcher' => (bool) data_get($this->defaults, 'header.theme_switcher', false),
                'user_menu' => (bool) data_get($this->defaults, 'header.user_menu', true),
                'mobile_search_mode' => data_get($this->defaults, 'header.mobile_search_mode', 'overlay'),
            ],
            'theme' => [
                'default' => data_get($this->defaults, 'theme.default', 'corporate-blue'),
                'dark_mode' => data_get($this->defaults, 'theme.dark_mode', 'class'),
                'accent' => data_get($this->defaults, 'theme.accent', 'blue'),
            ],
            'navigation' => [
                'cache_ttl' => (int) data_get($this->defaults, 'navigation.cache_ttl', 3600),
                'active_strategy' => data_get($this->defaults, 'navigation.active_strategy', 'url-prefix'),
                'max_depth' => (int) data_get($this->defaults, 'navigation.max_depth', 2),
            ],
        ];
    }

    public function stored(): array
    {
        $value = Setting::getValue(self::SETTING_KEY);

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function save(array $payload): void
    {
        $normalized = $this->normalize($payload);

        Setting::setValue(
            self::SETTING_KEY,
            json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'admin_layout',
            'json'
        );

        Setting::setValue(
            'admin_sidebar_theme',
            data_get($normalized, 'theme.default', 'corporate-blue'),
            'admin_layout',
            'text'
        );

        Cache::forget('admin.menus');
        session(['admin_theme' => data_get($normalized, 'theme.default', 'corporate-blue')]);
    }

    public function reset(): void
    {
        Setting::where('key', self::SETTING_KEY)->delete();
        Setting::where('key', 'admin_sidebar_theme')->delete();
        Cache::forget('admin.menus');
        session()->forget('admin_theme');
    }

    private function normalize(array $payload): array
    {
        $defaults = $this->defaults();

        return [
            'locale' => $this->in((string) ($payload['locale'] ?? $defaults['locale']), ['vi', 'en'], $defaults['locale']),
            'layout' => [
                'preset' => $this->in(data_get($payload, 'layout.preset'), ['default', 'data-heavy', 'focus', 'settings'], data_get($defaults, 'layout.preset')),
                'container' => $this->in(data_get($payload, 'layout.container'), ['full', 'narrow', '7xl', 'screen-2xl'], data_get($defaults, 'layout.container')),
                'density' => $this->in(data_get($payload, 'layout.density'), ['comfortable', 'compact', 'dense'], data_get($defaults, 'layout.density')),
                'sticky_header' => (bool) data_get($payload, 'layout.sticky_header', data_get($defaults, 'layout.sticky_header')),
                'show_footer' => (bool) data_get($payload, 'layout.show_footer', data_get($defaults, 'layout.show_footer')),
            ],
            'sidebar' => [
                'enabled' => (bool) data_get($payload, 'sidebar.enabled', data_get($defaults, 'sidebar.enabled')),
                'desktop_collapsible' => (bool) data_get($payload, 'sidebar.desktop_collapsible', data_get($defaults, 'sidebar.desktop_collapsible')),
                'mobile_drawer' => (bool) data_get($payload, 'sidebar.mobile_drawer', data_get($defaults, 'sidebar.mobile_drawer')),
                'persist_state' => (bool) data_get($payload, 'sidebar.persist_state', data_get($defaults, 'sidebar.persist_state')),
                'show_footer_profile' => (bool) data_get($payload, 'sidebar.show_footer_profile', data_get($defaults, 'sidebar.show_footer_profile')),
            ],
            'header' => [
                'sticky' => (bool) data_get($payload, 'header.sticky', data_get($defaults, 'header.sticky')),
                'search' => (bool) data_get($payload, 'header.search', data_get($defaults, 'header.search')),
                'notifications' => (bool) data_get($payload, 'header.notifications', data_get($defaults, 'header.notifications')),
                'theme_switcher' => (bool) data_get($payload, 'header.theme_switcher', data_get($defaults, 'header.theme_switcher')),
                'user_menu' => (bool) data_get($payload, 'header.user_menu', data_get($defaults, 'header.user_menu')),
                'mobile_search_mode' => $this->in(data_get($payload, 'header.mobile_search_mode'), ['overlay'], data_get($defaults, 'header.mobile_search_mode')),
            ],
            'theme' => [
                'default' => $this->in(data_get($payload, 'theme.default'), array_keys(config('admin.sidebar.themes', [])), data_get($defaults, 'theme.default')),
                'dark_mode' => $this->in(data_get($payload, 'theme.dark_mode'), ['class'], data_get($defaults, 'theme.dark_mode')),
                'accent' => $this->in(data_get($payload, 'theme.accent'), ['blue', 'indigo', 'emerald', 'rose', 'amber'], data_get($defaults, 'theme.accent')),
            ],
            'navigation' => [
                'cache_ttl' => max(60, min(86400, (int) data_get($payload, 'navigation.cache_ttl', data_get($defaults, 'navigation.cache_ttl')))),
                'active_strategy' => $this->in(data_get($payload, 'navigation.active_strategy'), ['url-prefix'], data_get($defaults, 'navigation.active_strategy')),
                'max_depth' => max(1, min(3, (int) data_get($payload, 'navigation.max_depth', data_get($defaults, 'navigation.max_depth')))),
            ],
        ];
    }

    private function in(mixed $value, array $allowed, mixed $fallback): mixed
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
