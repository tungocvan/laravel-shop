<?php

namespace Modules\Admin\Support;

use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Setting;

class AdminLayoutManager
{
    private const SETTING_KEY = 'admin_layout_config';
    private const SPACING_SCALE = ['0', '1', '2', '3', '4', '5', '6', '8', '10', '12'];

    private array $defaults;

    public function __construct() { $this->defaults = config('admin.admin', []); }

    public function config(): array { return array_replace_recursive($this->defaults(), $this->stored()); }

    public function defaults(): array
    {
        return [
            'locale' => $this->defaults['locale'] ?? 'vi',
            'layout' => [
                'preset' => data_get($this->defaults, 'layout.preset', 'default'), 'container' => data_get($this->defaults, 'layout.container', 'screen-2xl'), 'density' => data_get($this->defaults, 'layout.density', 'comfortable'), 'sticky_header' => (bool) data_get($this->defaults, 'layout.sticky_header', true), 'show_footer' => (bool) data_get($this->defaults, 'layout.show_footer', false),
                'spacing' => ['content_padding_x' => data_get($this->defaults, 'layout.spacing.content_padding_x', '6'), 'content_padding_top' => data_get($this->defaults, 'layout.spacing.content_padding_top', '6'), 'content_padding_bottom' => data_get($this->defaults, 'layout.spacing.content_padding_bottom', '8'), 'section_gap' => data_get($this->defaults, 'layout.spacing.section_gap', '6'), 'tablet_padding_x' => data_get($this->defaults, 'layout.spacing.tablet_padding_x', '5'), 'mobile_padding_x' => data_get($this->defaults, 'layout.spacing.mobile_padding_x', '4')],
                'surface' => ['page_background' => data_get($this->defaults, 'layout.surface.page_background', 'system'), 'content_surface' => data_get($this->defaults, 'layout.surface.content_surface', 'transparent'), 'border' => data_get($this->defaults, 'layout.surface.border', 'system'), 'radius' => data_get($this->defaults, 'layout.surface.radius', 'lg')],
                'behavior' => ['reduced_motion' => (bool) data_get($this->defaults, 'layout.behavior.reduced_motion', true)],
            ],
            'design' => $this->defaults['design'] ?? [],
            'sidebar' => ['enabled' => (bool) data_get($this->defaults, 'sidebar.enabled', true), 'expanded_width' => data_get($this->defaults, 'sidebar.expanded_width', '16rem'), 'collapsed_width' => data_get($this->defaults, 'sidebar.collapsed_width', '5rem'), 'desktop_collapsible' => (bool) data_get($this->defaults, 'sidebar.desktop_collapsible', true), 'mobile_drawer' => (bool) data_get($this->defaults, 'sidebar.mobile_drawer', true), 'persist_state' => (bool) data_get($this->defaults, 'sidebar.persist_state', true), 'show_footer_profile' => (bool) data_get($this->defaults, 'sidebar.show_footer_profile', true), 'navigation_search_threshold' => (int) data_get($this->defaults, 'sidebar.navigation_search_threshold', 12)],
            'header' => $this->headerDefaults(),
            'footer' => ['show_app_name' => (bool) data_get($this->defaults, 'footer.show_app_name', true), 'show_environment' => (bool) data_get($this->defaults, 'footer.show_environment', true)],
            'theme' => ['default' => data_get($this->defaults, 'theme.default', 'corporate-blue'), 'dark_mode' => data_get($this->defaults, 'theme.dark_mode', 'class'), 'accent' => data_get($this->defaults, 'theme.accent', 'blue')],
            'navigation' => ['cache_ttl' => (int) data_get($this->defaults, 'navigation.cache_ttl', 3600), 'active_strategy' => data_get($this->defaults, 'navigation.active_strategy', 'url-prefix'), 'max_depth' => (int) data_get($this->defaults, 'navigation.max_depth', 2)],
        ];
    }

    private function headerDefaults(): array
    {
        return [
            'height' => data_get($this->defaults, 'header.height', '4rem'), 'sticky' => (bool) data_get($this->defaults, 'header.sticky', true), 'search' => (bool) data_get($this->defaults, 'header.search', true), 'notifications' => (bool) data_get($this->defaults, 'header.notifications', true), 'theme_switcher' => (bool) data_get($this->defaults, 'header.theme_switcher', false), 'user_menu' => (bool) data_get($this->defaults, 'header.user_menu', true), 'mobile_search_mode' => data_get($this->defaults, 'header.mobile_search_mode', 'overlay'),
            'brand' => ['enabled' => (bool) data_get($this->defaults, 'header.brand.enabled', true), 'logo' => data_get($this->defaults, 'header.brand.logo'), 'logo_size' => data_get($this->defaults, 'header.brand.logo_size', '32'), 'show_title' => (bool) data_get($this->defaults, 'header.brand.show_title', true), 'title' => data_get($this->defaults, 'header.brand.title'), 'show_subtitle' => (bool) data_get($this->defaults, 'header.brand.show_subtitle', false), 'subtitle' => data_get($this->defaults, 'header.brand.subtitle'), 'url' => data_get($this->defaults, 'header.brand.url', '/admin')],
            'user_menu_config' => ['show_avatar' => (bool) data_get($this->defaults, 'header.user_menu_config.show_avatar', true), 'show_name' => (bool) data_get($this->defaults, 'header.user_menu_config.show_name', true), 'show_email' => (bool) data_get($this->defaults, 'header.user_menu_config.show_email', true), 'show_role' => (bool) data_get($this->defaults, 'header.user_menu_config.show_role', false), 'items' => (array) data_get($this->defaults, 'header.user_menu_config.items', [])],
            'actions' => ['items' => (array) data_get($this->defaults, 'header.actions.items', []), 'mobile_overflow' => (bool) data_get($this->defaults, 'header.actions.mobile_overflow', true)],
            'presentation' => ['mode' => data_get($this->defaults, 'header.presentation.mode', 'balanced'), 'padding_x' => data_get($this->defaults, 'header.presentation.padding_x', '6'), 'action_gap' => data_get($this->defaults, 'header.presentation.action_gap', '2'), 'background' => data_get($this->defaults, 'header.presentation.background', 'system'), 'divider' => data_get($this->defaults, 'header.presentation.divider', 'subtle'), 'shadow' => data_get($this->defaults, 'header.presentation.shadow', 'subtle'), 'backdrop_blur' => (bool) data_get($this->defaults, 'header.presentation.backdrop_blur', true)],
            'responsive' => ['mobile_brand' => data_get($this->defaults, 'header.responsive.mobile_brand', 'logo-only'), 'hide_title_on_mobile' => (bool) data_get($this->defaults, 'header.responsive.hide_title_on_mobile', true), 'overflow_secondary_actions' => (bool) data_get($this->defaults, 'header.responsive.overflow_secondary_actions', true)],
        ];
    }

    public function stored(): array
    {
        $value = Setting::getValue(self::SETTING_KEY);
        if (is_array($value)) return $value;
        if (! is_string($value) || trim($value) === '') return [];
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
        Setting::where('key', self::SETTING_KEY)->delete(); Setting::where('key', 'admin_sidebar_theme')->delete(); Cache::forget('admin.menus'); session()->forget('admin_theme');
    }

    private function normalize(array $payload): array
    {
        $defaults = $this->defaults(); $sidebarThemes = app(ThemeManager::class)->all();
        return [
            'locale' => $this->in((string) ($payload['locale'] ?? $defaults['locale']), ['vi', 'en'], $defaults['locale']),
            'layout' => [
                'preset' => $this->in(data_get($payload, 'layout.preset'), ['default', 'data-heavy', 'focus', 'settings'], data_get($defaults, 'layout.preset')), 'container' => $this->in(data_get($payload, 'layout.container'), ['full', 'narrow', '7xl', 'screen-2xl'], data_get($defaults, 'layout.container')), 'density' => $this->in(data_get($payload, 'layout.density'), ['comfortable', 'compact', 'dense'], data_get($defaults, 'layout.density')), 'sticky_header' => (bool) data_get($payload, 'layout.sticky_header', data_get($defaults, 'layout.sticky_header')), 'show_footer' => (bool) data_get($payload, 'layout.show_footer', data_get($defaults, 'layout.show_footer')),
                'spacing' => ['content_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_x'), data_get($defaults, 'layout.spacing.content_padding_x')), 'content_padding_top' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_top'), data_get($defaults, 'layout.spacing.content_padding_top')), 'content_padding_bottom' => $this->spacing(data_get($payload, 'layout.spacing.content_padding_bottom'), data_get($defaults, 'layout.spacing.content_padding_bottom')), 'section_gap' => $this->spacing(data_get($payload, 'layout.spacing.section_gap'), data_get($defaults, 'layout.spacing.section_gap')), 'tablet_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.tablet_padding_x'), data_get($defaults, 'layout.spacing.tablet_padding_x')), 'mobile_padding_x' => $this->spacing(data_get($payload, 'layout.spacing.mobile_padding_x'), data_get($defaults, 'layout.spacing.mobile_padding_x'))],
                'surface' => ['page_background' => $this->in(data_get($payload, 'layout.surface.page_background'), ['system', 'white', 'slate-50'], data_get($defaults, 'layout.surface.page_background')), 'content_surface' => $this->in(data_get($payload, 'layout.surface.content_surface'), ['transparent', 'system', 'white'], data_get($defaults, 'layout.surface.content_surface')), 'border' => $this->in(data_get($payload, 'layout.surface.border'), ['system', 'none'], data_get($defaults, 'layout.surface.border')), 'radius' => $this->in(data_get($payload, 'layout.surface.radius'), ['none', 'sm', 'md', 'lg'], data_get($defaults, 'layout.surface.radius'))],
                'behavior' => ['reduced_motion' => (bool) data_get($payload, 'layout.behavior.reduced_motion', data_get($defaults, 'layout.behavior.reduced_motion'))],
            ],
            'design' => app(\Modules\Admin\Services\AdminDesignService::class)->sanitize((array) data_get($payload, 'design', data_get($defaults, 'design', []))),
            'sidebar' => ['enabled' => (bool) data_get($payload, 'sidebar.enabled', data_get($defaults, 'sidebar.enabled')), 'expanded_width' => $this->in(data_get($payload, 'sidebar.expanded_width'), ['16rem'], data_get($defaults, 'sidebar.expanded_width')), 'collapsed_width' => $this->in(data_get($payload, 'sidebar.collapsed_width'), ['5rem'], data_get($defaults, 'sidebar.collapsed_width')), 'desktop_collapsible' => (bool) data_get($payload, 'sidebar.desktop_collapsible', data_get($defaults, 'sidebar.desktop_collapsible')), 'mobile_drawer' => (bool) data_get($payload, 'sidebar.mobile_drawer', data_get($defaults, 'sidebar.mobile_drawer')), 'persist_state' => (bool) data_get($payload, 'sidebar.persist_state', data_get($defaults, 'sidebar.persist_state')), 'show_footer_profile' => (bool) data_get($payload, 'sidebar.show_footer_profile', data_get($defaults, 'sidebar.show_footer_profile')), 'navigation_search_threshold' => max(4, min(50, (int) data_get($payload, 'sidebar.navigation_search_threshold', data_get($defaults, 'sidebar.navigation_search_threshold', 12))))],
            'header' => $this->normalizeHeader((array) data_get($payload, 'header', []), $defaults['header']),
            'footer' => ['show_app_name' => (bool) data_get($payload, 'footer.show_app_name', data_get($defaults, 'footer.show_app_name')), 'show_environment' => (bool) data_get($payload, 'footer.show_environment', data_get($defaults, 'footer.show_environment'))],
            'theme' => ['default' => $this->in(data_get($payload, 'theme.default'), $sidebarThemes, data_get($defaults, 'theme.default')), 'dark_mode' => $this->in(data_get($payload, 'theme.dark_mode'), ['class'], data_get($defaults, 'theme.dark_mode')), 'accent' => $this->in(data_get($payload, 'theme.accent'), ['blue', 'indigo', 'emerald', 'rose', 'amber'], data_get($defaults, 'theme.accent'))],
            'navigation' => ['cache_ttl' => max(60, min(86400, (int) data_get($payload, 'navigation.cache_ttl', data_get($defaults, 'navigation.cache_ttl')))), 'active_strategy' => $this->in(data_get($payload, 'navigation.active_strategy'), ['url-prefix'], data_get($defaults, 'navigation.active_strategy')), 'max_depth' => max(1, min(3, (int) data_get($payload, 'navigation.max_depth', data_get($defaults, 'navigation.max_depth'))))],
        ];
    }

    private function normalizeHeader(array $header, array $defaults): array
    {
        return [
            'height' => $this->in(data_get($header, 'height'), ['3.5rem', '4rem', '4.5rem'], data_get($defaults, 'height')), 'sticky' => (bool) data_get($header, 'sticky', data_get($defaults, 'sticky')), 'search' => (bool) data_get($header, 'search', data_get($defaults, 'search')), 'notifications' => (bool) data_get($header, 'notifications', data_get($defaults, 'notifications')), 'theme_switcher' => (bool) data_get($header, 'theme_switcher', data_get($defaults, 'theme_switcher')), 'user_menu' => (bool) data_get($header, 'user_menu', data_get($defaults, 'user_menu')), 'mobile_search_mode' => $this->in(data_get($header, 'mobile_search_mode'), ['overlay'], data_get($defaults, 'mobile_search_mode')),
            'brand' => ['enabled' => (bool) data_get($header, 'brand.enabled', data_get($defaults, 'brand.enabled')), 'logo' => $this->nullableString(data_get($header, 'brand.logo')), 'logo_size' => $this->in((string) data_get($header, 'brand.logo_size'), ['24', '28', '32', '36', '40'], data_get($defaults, 'brand.logo_size')), 'show_title' => (bool) data_get($header, 'brand.show_title', data_get($defaults, 'brand.show_title')), 'title' => $this->nullableString(data_get($header, 'brand.title')), 'show_subtitle' => (bool) data_get($header, 'brand.show_subtitle', data_get($defaults, 'brand.show_subtitle')), 'subtitle' => $this->nullableString(data_get($header, 'brand.subtitle')), 'url' => $this->safePath(data_get($header, 'brand.url'), data_get($defaults, 'brand.url'))],
            'user_menu_config' => ['show_avatar' => (bool) data_get($header, 'user_menu_config.show_avatar', data_get($defaults, 'user_menu_config.show_avatar')), 'show_name' => (bool) data_get($header, 'user_menu_config.show_name', data_get($defaults, 'user_menu_config.show_name')), 'show_email' => (bool) data_get($header, 'user_menu_config.show_email', data_get($defaults, 'user_menu_config.show_email')), 'show_role' => (bool) data_get($header, 'user_menu_config.show_role', data_get($defaults, 'user_menu_config.show_role')), 'items' => array_values((array) data_get($header, 'user_menu_config.items', []))],
            'actions' => ['items' => array_values((array) data_get($header, 'actions.items', [])), 'mobile_overflow' => (bool) data_get($header, 'actions.mobile_overflow', data_get($defaults, 'actions.mobile_overflow'))],
            'presentation' => ['mode' => $this->in(data_get($header, 'presentation.mode'), ['balanced', 'compact', 'action-heavy'], data_get($defaults, 'presentation.mode')), 'padding_x' => $this->spacing(data_get($header, 'presentation.padding_x'), data_get($defaults, 'presentation.padding_x')), 'action_gap' => $this->spacing(data_get($header, 'presentation.action_gap'), data_get($defaults, 'presentation.action_gap')), 'background' => $this->in(data_get($header, 'presentation.background'), ['system', 'white', 'transparent'], data_get($defaults, 'presentation.background')), 'divider' => $this->in(data_get($header, 'presentation.divider'), ['subtle', 'none'], data_get($defaults, 'presentation.divider')), 'shadow' => $this->in(data_get($header, 'presentation.shadow'), ['none', 'subtle'], data_get($defaults, 'presentation.shadow')), 'backdrop_blur' => (bool) data_get($header, 'presentation.backdrop_blur', data_get($defaults, 'presentation.backdrop_blur'))],
            'responsive' => ['mobile_brand' => $this->in(data_get($header, 'responsive.mobile_brand'), ['logo-only', 'logo-title', 'hidden'], data_get($defaults, 'responsive.mobile_brand')), 'hide_title_on_mobile' => (bool) data_get($header, 'responsive.hide_title_on_mobile', data_get($defaults, 'responsive.hide_title_on_mobile')), 'overflow_secondary_actions' => (bool) data_get($header, 'responsive.overflow_secondary_actions', data_get($defaults, 'responsive.overflow_secondary_actions'))],
        ];
    }

    private function nullableString(mixed $value): ?string { $value = is_string($value) ? trim($value) : ''; return $value === '' ? null : mb_substr($value, 0, 255); }
    private function safePath(mixed $value, mixed $fallback): string { $value = is_string($value) ? trim($value) : ''; return str_starts_with($value, '/') && ! str_starts_with($value, '//') ? mb_substr($value, 0, 255) : (string) $fallback; }
    private function spacing(mixed $value, mixed $fallback): string { return (string) $this->in((string) $value, self::SPACING_SCALE, (string) $fallback); }
    private function in(mixed $value, array $allowed, mixed $fallback): mixed { return in_array($value, $allowed, true) ? $value : $fallback; }
}
