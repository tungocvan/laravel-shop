<?php

namespace Modules\Admin\Services;

use Illuminate\Support\Facades\Cache;
use Modules\System\Models\Setting;

class AdminDesignService
{
    private const SETTING_KEY = 'admin_design_config';

    private const CACHE_KEY = 'admin.design.tokens';

    private const COLOR_VALUES = [
        'white' => '#ffffff',
        'slate-50' => '#f8fafc',
        'slate-200' => '#e2e8f0',
        'slate-500' => '#64748b',
        'slate-700' => '#334155',
        'slate-900' => '#0f172a',
        'indigo-500' => '#6366f1',
        'indigo-600' => '#4f46e5',
        'emerald-600' => '#059669',
        'amber-500' => '#f59e0b',
        'rose-600' => '#e11d48',
        'sky-600' => '#0284c7',
    ];

    private const FONT_FAMILIES = [
        'sans' => 'ui-sans-serif, system-ui, sans-serif',
    ];

    private const FONT_SIZES = [
        'xs' => '0.75rem',
        'sm' => '0.875rem',
        'base' => '1rem',
        'lg' => '1.125rem',
        '2xl' => '1.5rem',
    ];

    private const FONT_WEIGHTS = [
        'normal' => '400',
        'medium' => '500',
        'semibold' => '600',
        'bold' => '700',
    ];

    private const SPACING_VALUES = [
        '1' => '0.25rem',
        '2' => '0.5rem',
        '3' => '0.75rem',
        '4' => '1rem',
        '6' => '1.5rem',
        '8' => '2rem',
    ];

    private const RADIUS_VALUES = [
        'sm' => '0.25rem',
        'md' => '0.375rem',
        'lg' => '0.5rem',
        'xl' => '0.75rem',
    ];

    public function defaults(): array
    {
        return $this->sanitize(config('admin.admin.design', []));
    }

    public function tokens(): array
    {
        return Cache::remember(self::CACHE_KEY, 3600, function (): array {
            return $this->sanitize(array_replace_recursive(
                config('admin.admin.design', []),
                $this->stored()
            ));
        });
    }

    public function sanitize(array $tokens): array
    {
        $defaults = config('admin.admin.design', []);

        return [
            'typography' => [
                'font_family' => $this->allowed(
                    data_get($tokens, 'typography.font_family'),
                    array_keys(self::FONT_FAMILIES),
                    data_get($defaults, 'typography.font_family', 'sans')
                ),
                'body_size' => $this->allowed(
                    data_get($tokens, 'typography.body_size'),
                    array_keys(self::FONT_SIZES),
                    data_get($defaults, 'typography.body_size', 'sm')
                ),
                'page_title_size' => $this->allowed(
                    data_get($tokens, 'typography.page_title_size'),
                    array_keys(self::FONT_SIZES),
                    data_get($defaults, 'typography.page_title_size', '2xl')
                ),
                'heading_weight' => $this->allowed(
                    data_get($tokens, 'typography.heading_weight'),
                    array_keys(self::FONT_WEIGHTS),
                    data_get($defaults, 'typography.heading_weight', 'semibold')
                ),
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
            '--admin-space-tight' => self::SPACING_VALUES[$tokens['spacing']['tight']],
            '--admin-space-control' => self::SPACING_VALUES[$tokens['spacing']['control']],
            '--admin-space-content' => self::SPACING_VALUES[$tokens['spacing']['content']],
            '--admin-space-section' => self::SPACING_VALUES[$tokens['spacing']['section']],
            '--admin-radius-control' => self::RADIUS_VALUES[$tokens['radius']['control']],
            '--admin-radius-panel' => self::RADIUS_VALUES[$tokens['radius']['panel']],
            '--admin-radius-overlay' => self::RADIUS_VALUES[$tokens['radius']['overlay']],
        ];
    }

    public function save(array $tokens): void
    {
        Setting::setValue(
            self::SETTING_KEY,
            json_encode($this->sanitize($tokens), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
            'admin_design',
            'json'
        );

        Cache::forget(self::CACHE_KEY);
    }

    public function reset(): void
    {
        Setting::where('key', self::SETTING_KEY)->delete();
        Cache::forget(self::CACHE_KEY);
    }

    private function stored(): array
    {
        $value = Setting::getValue(self::SETTING_KEY);

        if (! is_string($value) || trim($value) === '') {
            return [];
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : [];
    }

    private function sanitizeColors(array $tokens, array $defaults): array
    {
        $keys = [
            'surface_base',
            'surface_raised',
            'text_primary',
            'text_secondary',
            'text_muted',
            'border_subtle',
            'accent',
            'focus_ring',
            'success',
            'warning',
            'danger',
            'info',
        ];

        $colors = [];

        foreach ($keys as $key) {
            $colors[$key] = $this->allowed(
                data_get($tokens, 'colors.' . $key),
                array_keys(self::COLOR_VALUES),
                data_get($defaults, 'colors.' . $key, $this->fallbackColor($key))
            );
        }

        return $colors;
    }

    private function fallbackColor(string $key): string
    {
        return match ($key) {
            'surface_base' => 'slate-50',
            'surface_raised' => 'white',
            'text_primary' => 'slate-900',
            'text_secondary' => 'slate-700',
            'text_muted' => 'slate-500',
            'border_subtle' => 'slate-200',
            'accent' => 'indigo-600',
            'focus_ring' => 'indigo-500',
            'success' => 'emerald-600',
            'warning' => 'amber-500',
            'danger' => 'rose-600',
            'info' => 'sky-600',
            default => 'slate-900',
        };
    }

    private function allowed(mixed $value, array $allowed, mixed $fallback): mixed
    {
        return in_array($value, $allowed, true) ? $value : $fallback;
    }
}
