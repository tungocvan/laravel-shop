<?php

namespace Modules\Admin\Support;

use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Setting;

class AdminLayoutManager
{
    private const SETTING_KEY = 'admin_layout_config';

    private const SPACING_SCALE = ['0', '1', '2', '3', '4', '5', '6', '8', '10', '12'];

    private array $defaults;

    public function __construct()
    {
        $this->defaults = config('admin.admin', []);
    }

    public function config(): array
    {
        return array_replace_recursive($this->defaults(), $this->stored());
    }

    public function defaults(): array
    {
        return [
            'locale' => $this->defaults['locale'] ?? 'vi',
            'layout' => [
                'preset' => data_get($this->defaults, 'layout.preset', 'default'),
                'container' => data_get($this->defaults, 'layout.container', 'screen-2xl'),
                'density' => data_get($this->defaults, 'layout.density', 'comfortable'),
                'sticky_header' => (bool) data_get($this->defaults, 'layout.sticky_header', true),
                'show_footer' => (bool) data_get($this->defaults, 'layout.show_footer', false),
                'spacing' => [
                    'content_padding_x' => data_get($this->defaults, 'layout.spacing.content_padding_x', '6'),
                    'content_padding_top' => data_get($this->defaults, 'layout.spacing.content_padding_top', '6'),
                    'content_padding_bottom' => data_get($this->defaults, 'layout.spacing.content_padding_bottom', '8'),
                    'section_gap' => data_get($this->defaults, 'layout.spacing.section_gap', '6'),
                    'tablet_padding_x' => data_get($this->defaults, 'layout.spacing.tablet_padding_x', '5'),
                    'mobile_padding_x' => data_get($this->defaults, 'layout.spacing.mobile_padding_x', '4'),
                ],
                'surface' => [
                    'page_background' => data_get($this->defaults, 'layout.surface.page_background', 'system'),
                    'content_surface' => data_get($this->defaults, 'layout.surface.content_surface', 'transparent'),
                    'border' => data_get($this->defaults, 'layout.surface.border', 'system'),
                    'radius' => data_get($this->defaults, 'layout.surface.radius', 'lg'),
                ],
                'behavior' => [
                    'reduced_motion' => (bool) data_get($this->defaults, 'layout.behavior.reduced_motion', true),
                ],
            ],
            'design' => $this->defaults['design'] ?? [],
            'sidebar' => [
                'enabled' => (bool) data_get($this->defaults, 'sidebar.enabled', true),
                'expanded_width' => data_get($this->defaults, 'sidebar.expanded_width', '16rem'),
                'collapsed_width' => data_get($this->defaults, 'sidebar.collapsed_width', '5rem'),
                'desktop_collapsible' => (bool) data_get($this->defaults, 'sidebar.desktop_collapsible', true),
                'mobile_drawer' => (bool) data_get($this->defaults, 'sidebar.mobile_drawer', true),
                'persist_state' => (bool) data_get($this->defaults, 'sidebar.persist_state', true),
                'show_footer_profile' => (bool) data_get($this->defaults, 'sidebar.show_footer_profile', true),
                'navigation_search_threshold' => (int) data_get($this->defaults, 'sidebar.navigation_search_threshold', 12),
            ],
            'header' => [
                'height' => data_get($this->defaults, 'header.height', '4rem'),
                'sticky' => (bool) data_get($this->defaults, 'header.sticky', true),
                'search' => (bool) data_get($this->defaults, 'header.search', true),
                'notifications' => (bool) data_get($this->defaults, 'header.notifications', true),
                'theme_switcher' => (bool) data_get($this->defaults, 'header.theme_switcher', false),
                'user_menu' => (bool) data_get($this->defaults, 'header.user_menu', true),
                'mobile_search_mode' => data_get($this->defaults, 'header.mobile_search_mode', 'overlay'),
            ],
            'footer' => [
                'show_app_name' => (bool) data_get($this->defaults, 'footer.show_app_name', true),
                'show_environment' => (bool) data_get($this->defaults, 'footer.show_environment', true),
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

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    public function save(array $payload): void
    {
        $normalized = $this->normalize($payload);

        Setting::setValue(self::SETTING_KEY, $normalized, 'admin_layout', 'json');
        Setting::setValue('admin_sidebar_theme', data_get($normalized, 'theme.default', 'corporate-blue'), 'admin_layout', 'text');

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
        $sidebarThemes = app(ThemeManager::class)->all();

        return [
            'locale' => $this->in((string) ($payload['locale'] ?? $defaults['locale']), ['vi', 'en'], $defaults['locale']),
            'layout' => [
                'preset' => $this->in(data_get($payload, 'layout.preset'), ['default', 'data-heavy', 'focus', 'settings'], data_get($defaults, 'layout.preset')),
                'container' => $this->in(data_get($payload, 'layout.container'), ['full', 'narrow', '7xl', 'screen-2xl'], data_get($defaults, 'layout.container')),
                'density' => $this->in(data_get($payload, 'layout.density'), ['comfortable', 'compact', 'dense'], data_get($defaults, 'layout.density')),
                'sticky_header' => (bool) data_get($payload, 'layout.sticky_header', data_get($defaults, 'layout.sticky_header')),
                'show_footer' => (bool) data_get($payload, 'layout.show_footer', data_get($defaults, 'layout.show_footer')),
                'spacing' => [
                    'content_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_x'), data_get($defaults, 'layout.spacing.content_padding_x')),
                    'content_padding_top' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_top'), data_get($defaults, 'layout.spacing.content_padding_top')),
                    'content_padding_bottom' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_bottom'), data_get($defaults, 'layout.spacing.content_padding_bottom')),
                    'section_gap' => $this->spacing(data_get($payload, 'layout.spacing.section_gap'), data_get($defaults, 'layout.spacing.section_gap')),
                    'tablet_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.tablet_padding_x'), data_get($defaults, 'layout.spacing.tablet_padding_x')),
                    'mobile_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.mobile_padding_x'), data_get($defaults, 'layout.spacing.mobile_padding_x')),
                ],
                'surface' => [
                    'page_background' => $this->in(data_get($payload, 'layout.surface.page_background'), ['system', 'white', 'slate-50'], data_get($defaults, 'layout.surface.page_background')),
                    'content_surface' => $this->in(data_get($payload, 'layout.surface.content_surface'), ['transparent', 'system', 'white'], data_get($defaults, 'layout.surface.content_surface')),
                    'border' => $this->in(data_get($payload, 'layout.surface.border'), ['system', 'none'], data_get($defaults, 'layout.surface.border')),
                    'radius' => $this->in(data_get($payload, 'layout.surface.radius'), ['none', 'sm', 'md', 'lg'], data_get($defaults, 'layout.surface.radius')),
                ],
                'behavior' => [
                    'reduced_motion' => (bool) data_get($payload, 'layout.behavior.reduced_motion', data_get($defaults, 'layout.behavior.reduced_motion')),
                ],
            ],
            'design' => app(\Modules\Admin\Services\AdminDesignService::class)->sanitize((array) data_get($payload, 'design', data_get($defaults, 'design', []))),
            'sidebar' => [
                'enabled' => (bool) data_get($payload, 'sidebar.enabled', data_get($defaults, 'sidebar.enabled')),
                'expanded_width' => $this->in(data_get($payload, 'sidebar.expanded_width'), ['16rem'], data_get($defaults, 'sidebar.expanded_width')),
                'collapsed_width' => $this->in(data_get($payload, 'sidebar.collapsed_width'), ['5rem'], data_get($defaults, 'sidebar.collapsed_width')),
                'desktop_collapsible' => (bool) data_get($payload, 'sidebar.desktop_collapsible', data_get($defaults, 'sidebar.desktop_collapsible')),
                'mobile_drawer' => (bool) data_get($payload, 'sidebar.mobile_drawer', data_get($defaults, 'sidebar.mobile_drawer')),
                'persist_state' => (bool) data_get($payload, 'sidebar.persist_state', data_get($defaults, 'sidebar.persist_state')),
                'show_footer_profile' => (bool) data_get($payload, 'sidebar.show_footer_profile', data_get($defaults, 'sidebar.show_footer_profile')),
                'navigation_search_threshold' => max(4, min(50, (int) data_get($payload, 'sidebar.navigation_search_threshold', data_get($defaults, 'sidebar.navigation_search_threshold', 12)))),
            ],
            'header' => [
                'height' => $this->in(data_get($payload, 'header.height'), ['4rem'], data_get($defaults, 'header.height')),
                'sticky' => (bool) data_get($payload, 'header.sticky', data_get($defaults, 'header.sticky')),
                'search' => (bool) data_get($payload, 'header.search', data_get($defaults, 'header.search')),
                'notifications' => (bool) data_get($payload, 'header.notifications', data_get($defaults, 'header.notifications')),
                'theme_switcher' => (bool) data_get($payload, 'header.theme_switcher', data_get($defaults, 'header.theme_switcher')),
                'user_menu' => (bool) data_get($payload, 'header.user_menu', data_get($defaults, 'header.user_menu')),
                'mobile_search_mode' => $this->in(data_get($payload, 'header.mobile_search_mode'), ['overlay'], data_get($defaults, 'header.mobile_search_mode')),
            ],
            'footer' => [
                'show_app_name' => (bool) data_get($payload, 'footer.show_app_name', data_get($defaults, 'footer.show_app_name')),
                'show_environment' => (bool) data_get($payload, 'footer.show_environment', data_get($defaults, 'footer.show_environment')),
            ],
            'theme' => [
                'default' => $this->in(data_get($payload, 'theme.default'), $sidebarThemes, data_get($defaults, 'theme.default')),
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

    private function spacing(mixed $value, mixed $fallback): string
    {
        return (string) $this->in((string) $value, self::SPACING_SCALE, (string) $fallback);
    }

    private function in(mixed $value, array $allowed, mixed $fallback): mixed
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
