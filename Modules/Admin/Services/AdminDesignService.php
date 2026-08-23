<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminDesignService
{
    private AdminLayoutManager $layoutManager;

    private const COLOR_VALUES = [
        'white' => '#ffffff',
        'slate-50' => '#f8fafc', 'slate-100' => '#f1f5f9', 'slate-200' => '#e2e8f0', 'slate-400' => '#94a3b8', 'slate-500' => '#64748b', 'slate-700' => '#334155', 'slate-900' => '#0f172a', 'slate-950' => '#020617',
        'indigo-50' => '#eef2ff', 'indigo-100' => '#e0e7ff', 'indigo-400' => '#818cf8', 'indigo-500' => '#6366f1', 'indigo-600' => '#4f46e5',
        'blue-50' => '#eff6ff', 'blue-100' => '#dbeafe', 'blue-500' => '#3b82f6', 'blue-600' => '#2563eb',
        'orange-50' => '#fff7ed', 'orange-100' => '#ffedd5', 'orange-200' => '#fed7aa', 'orange-500' => '#f97316', 'orange-600' => '#ea580c',
        'emerald-50' => '#ecfdf5', 'emerald-100' => '#d1fae5', 'emerald-500' => '#10b981', 'emerald-600' => '#059669',
        'amber-50' => '#fffbeb', 'amber-100' => '#fef3c7', 'amber-400' => '#fbbf24', 'amber-500' => '#f59e0b',
        'rose-50' => '#fff1f2', 'rose-100' => '#ffe4e6', 'rose-500' => '#f43f5e', 'rose-600' => '#e11d48',
        'sky-50' => '#f0f9ff', 'sky-100' => '#e0f2fe', 'sky-500' => '#0ea5e9', 'sky-600' => '#0284c7',
    ];

    private const SURFACE_COLOR_KEYS = [
        'white', 'slate-50', 'slate-100', 'slate-200', 'slate-900', 'slate-950',
        'indigo-50', 'indigo-100', 'blue-50', 'blue-100',
        'orange-50', 'orange-100', 'emerald-50', 'emerald-100',
        'amber-50', 'amber-100', 'rose-50', 'rose-100', 'sky-50', 'sky-100',
    ];

    private const FONT_FAMILIES = ['sans' => 'ui-sans-serif, system-ui, sans-serif'];
    private const FONT_SIZES = ['xs' => '0.75rem', 'sm' => '0.875rem', 'base' => '1rem', 'lg' => '1.125rem', '2xl' => '1.5rem'];
    private const FONT_WEIGHTS = ['normal' => '400', 'medium' => '500', 'semibold' => '600', 'bold' => '700'];
    private const SPACING_VALUES = ['1' => '0.25rem', '2' => '0.5rem', '3' => '0.75rem', '4' => '1rem', '6' => '1.5rem', '8' => '2rem'];
    private const RADIUS_VALUES = ['sm' => '0.25rem', 'md' => '0.375rem', 'lg' => '0.5rem', 'xl' => '0.75rem'];

    public function __construct(?AdminLayoutManager $layoutManager = null)
    {
        $this->layoutManager = $layoutManager ?? new AdminLayoutManager();
    }

    public function defaults(): array
    {
        return $this->sanitize(data_get($this->layoutManager->defaults(), 'design', config('admin.admin.design', [])));
    }

    public function tokens(): array
    {
        return $this->sanitize(data_get($this->layoutManager->config(), 'design', config('admin.admin.design', [])));
    }

    public function sanitize(array $tokens): array
    {
        $defaults = config('admin.admin.design', []);
        return [
            'typography' => [
                'font_family' => $this->allowed(data_get($tokens, 'typography.font_family'), array_keys(self::FONT_FAMILIES), data_get($defaults, 'typography.font_family', 'sans')),
                'body_size' => $this->allowed(data_get($tokens, 'typography.body_size'), array_keys(self::FONT_SIZES), data_get($defaults, 'typography.body_size', 'sm')),
                'page_title_size' => $this->allowed(data_get($tokens, 'typography.page_title_size'), array_keys(self::FONT_SIZES), data_get($defaults, 'typography.page_title_size', '2xl')),
                'heading_weight' => $this->allowed(data_get($tokens, 'typography.heading_weight'), array_keys(self::FONT_WEIGHTS), data_get($defaults, 'typography.heading_weight', 'semibold')),
            ],
            'colors' => $this->sanitizeColors($tokens, $defaults),
            'spacing' => [
                'tight' => $this->allowed(data_get($tokens, 'spacing.tight'), array_keys(self::SPACING_VALUES), data_get($defaults, 'spacing.tight', '2')),
                'control' => $this->allowed(data_get($tokens, 'spacing.control'), array_keys(self::SPACING_VALUES), data_get($defaults, 'spacing.control', '3')),
                'content' => $this->allowed(data_get($tokens, 'spacing.content'), array_keys(self::SPACING_VALUES), data_get($defaults, 'spacing.content', '4')),
                'section' => $this->allowed(data_get($tokens, 'spacing.section'), array_keys(self::SPACING_VALUES), data_get($defaults, 'spacing.section', '6')),
            ],
            'radius' => [
                'control' => $this->allowed(data_get($tokens, 'radius.control'), array_keys(self::RADIUS_VALUES), data_get($defaults, 'radius.control', 'lg')),
                'panel' => $this->allowed(data_get($tokens, 'radius.panel'), array_keys(self::RADIUS_VALUES), data_get($defaults, 'radius.panel', 'lg')),
                'overlay' => $this->allowed(data_get($tokens, 'radius.overlay'), array_keys(self::RADIUS_VALUES), data_get($defaults, 'radius.overlay', 'xl')),
            ],
        ];
    }

    public function cssVariables(?array $tokens = null): array
    {
        $tokens = $this->sanitize($tokens ?? $this->tokens());
        return [
            '--admin-font-family' => self::FONT_FAMILIES[$tokens['typography']['font_family']],
            '--admin-font-size-body' => self::FONT_SIZES[$tokens['typography']['body_size']],
            '--admin-font-size-page-title' => self::FONT_SIZES[$tokens['typography']['page_title_size']],
            '--admin-font-weight-heading' => self::FONT_WEIGHTS[$tokens['typography']['heading_weight']],
            '--admin-surface-base' => self::COLOR_VALUES[$tokens['colors']['surface_base']],
            '--admin-surface-raised' => self::COLOR_VALUES[$tokens['colors']['surface_raised']],
            '--admin-text-primary' => self::COLOR_VALUES[$tokens['colors']['text_primary']],
            '--admin-text-secondary' => self::COLOR_VALUES[$tokens['colors']['text_secondary']],
            '--admin-text-muted' => self::COLOR_VALUES[$tokens['colors']['text_muted']],
            '--admin-border-subtle' => self::COLOR_VALUES[$tokens['colors']['border_subtle']],
            '--admin-accent' => self::COLOR_VALUES[$tokens['colors']['accent']],
            '--admin-focus-ring' => self::COLOR_VALUES[$tokens['colors']['focus_ring']],
            '--admin-success' => self::COLOR_VALUES[$tokens['colors']['success']],
            '--admin-warning' => self::COLOR_VALUES[$tokens['colors']['warning']],
            '--admin-danger' => self::COLOR_VALUES[$tokens['colors']['danger']],
            '--admin-info' => self::COLOR_VALUES[$tokens['colors']['info']],
            '--admin-header-theme-background' => self::COLOR_VALUES[$tokens['colors']['header_background']],
            '--admin-footer-theme-background' => self::COLOR_VALUES[$tokens['colors']['footer_background']],
            '--admin-page-theme-background' => self::COLOR_VALUES[$tokens['colors']['page_background']],
            '--admin-content-theme-background' => self::COLOR_VALUES[$tokens['colors']['content_background']],
            '--admin-sidebar-header-theme-background' => self::COLOR_VALUES[$tokens['colors']['sidebar_header_background']],
            '--admin-sidebar-navigation-theme-background' => self::COLOR_VALUES[$tokens['colors']['sidebar_navigation_background']],
            '--admin-sidebar-footer-theme-background' => self::COLOR_VALUES[$tokens['colors']['sidebar_footer_background']],
            '--admin-space-tight' => self::SPACING_VALUES[$tokens['spacing']['tight']],
            '--admin-space-control' => self::SPACING_VALUES[$tokens['spacing']['control']],
            '--admin-space-content' => self::SPACING_VALUES[$tokens['spacing']['content']],
            '--admin-space-section' => self::SPACING_VALUES[$tokens['spacing']['section']],
            '--admin-radius-control' => self::RADIUS_VALUES[$tokens['radius']['control']],
            '--admin-radius-panel' => self::RADIUS_VALUES[$tokens['radius']['panel']],
            '--admin-radius-overlay' => self::RADIUS_VALUES[$tokens['radius']['overlay']],
        ];
    }

    public function colorOptions(): array { return self::COLOR_VALUES; }
    public function surfaceColorOptions(): array { return array_intersect_key(self::COLOR_VALUES, array_flip(self::SURFACE_COLOR_KEYS)); }
    public static function colorKeys(): array { return array_keys(self::COLOR_VALUES); }
    public static function surfaceColorKeys(): array { return self::SURFACE_COLOR_KEYS; }
    public function colorValue(mixed $token, string $fallback = '#ffffff'): string { return self::COLOR_VALUES[(string) $token] ?? $fallback; }

    public function contrastVariables(mixed $token): array
    {
        $dark = $this->isDark($this->colorValue($token));
        return $dark
            ? ['--admin-text-primary' => '#ffffff', '--admin-text-secondary' => '#e2e8f0', '--admin-text-muted' => '#cbd5e1', '--admin-border-subtle' => 'rgb(255 255 255 / 0.18)']
            : ['--admin-text-primary' => '#0f172a', '--admin-text-secondary' => '#334155', '--admin-text-muted' => '#64748b', '--admin-border-subtle' => 'rgb(15 23 42 / 0.12)'];
    }

    private function isDark(string $hex): bool
    {
        $hex = ltrim($hex, '#');
        if (strlen($hex) !== 6) return false;
        $r = hexdec(substr($hex, 0, 2)); $g = hexdec(substr($hex, 2, 2)); $b = hexdec(substr($hex, 4, 2));
        return ((0.2126 * $r + 0.7152 * $g + 0.0722 * $b) / 255) < 0.56;
    }

    private function sanitizeColors(array $tokens, array $defaults): array
    {
        $keys = ['surface_base', 'surface_raised', 'text_primary', 'text_secondary', 'text_muted', 'border_subtle', 'accent', 'focus_ring', 'success', 'warning', 'danger', 'info', 'header_background', 'footer_background', 'page_background', 'content_background', 'sidebar_header_background', 'sidebar_navigation_background', 'sidebar_footer_background'];
        $colors = [];
        foreach ($keys as $key) {
            $allowed = in_array($key, ['page_background', 'content_background'], true) ? self::SURFACE_COLOR_KEYS : array_keys(self::COLOR_VALUES);
            $colors[$key] = $this->allowed(data_get($tokens, 'colors.'.$key), $allowed, data_get($defaults, 'colors.'.$key, $this->fallbackColor($key)));
        }
        return $colors;
    }

    private function fallbackColor(string $key): string
    {
        return match ($key) {
            'surface_base', 'page_background' => 'slate-50',
            'surface_raised', 'header_background', 'footer_background', 'content_background', 'sidebar_header_background', 'sidebar_navigation_background', 'sidebar_footer_background' => 'white',
            'text_primary' => 'slate-900', 'text_secondary' => 'slate-700', 'text_muted' => 'slate-500', 'border_subtle' => 'slate-200',
            'accent' => 'indigo-600', 'focus_ring' => 'indigo-500', 'success' => 'emerald-600',
            'warning' => 'amber-500', 'danger' => 'rose-600', 'info' => 'sky-600', default => 'slate-900',
        };
    }

    private function allowed(mixed $value, array $allowed, mixed $fallback): mixed
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
