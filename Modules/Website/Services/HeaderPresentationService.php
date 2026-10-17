<?php

namespace Modules\Website\Services;

class HeaderPresentationService
{
    public function resolve(?array $input = null): array
    {
        $defaults = (array) config('website.header.presentation', []);
        $input = is_array($input) ? array_replace_recursive($defaults, $input) : $defaults;

        $containers = (array) config('website.header.presets.container', []);
        $sizes = (array) config('website.header.presets.size', []);
        $bounds = (array) config('website.header.bounds', []);
        $colors = is_array($input['colors'] ?? null) ? $input['colors'] : [];

        $mode = in_array($input['mode'] ?? null, ['basic', 'advanced'], true) ? $input['mode'] : 'basic';
        $container = array_key_exists($input['container'] ?? '', $containers) ? $input['container'] : 'standard';
        $size = array_key_exists($input['size'] ?? '', $sizes) ? $input['size'] : 'normal';
        $custom = is_array($input['custom'] ?? null) ? $input['custom'] : [];

        $containerWidth = $container === 'full'
            ? null
            : (int) ($containers[$container] ?? 1280);

        $heights = $sizes[$size] ?? $sizes['normal'] ?? ['desktop' => 80, 'tablet' => 72, 'mobile' => 64];

        if ($mode === 'advanced') {
            $containerWidth = $this->clamp($custom['container_width'] ?? $containerWidth ?? 1280, $bounds['container_width'] ?? [960, 1920]);
            $heights = [
                'desktop' => $this->clamp($custom['desktop_height'] ?? 80, $bounds['header_height'] ?? [52, 120]),
                'tablet' => $this->clamp($custom['tablet_height'] ?? 72, $bounds['header_height'] ?? [52, 120]),
                'mobile' => $this->clamp($custom['mobile_height'] ?? 64, $bounds['header_height'] ?? [52, 120]),
            ];
        }

        return [
            'mode' => $mode,
            'container' => $container,
            'container_width' => $containerWidth,
            'size' => $size,
            'heights' => $heights,
            'sticky' => (bool) ($input['sticky'] ?? true),
            'shadow' => in_array($input['shadow'] ?? null, ['none', 'soft', 'medium'], true) ? $input['shadow'] : 'soft',
            'inherit_colors' => (bool) ($input['inherit_colors'] ?? true),
            'colors' => [
                'background' => $this->color($colors['background'] ?? $input['background'] ?? '#ffffff', '#ffffff'),
                'foreground' => $this->color($colors['foreground'] ?? $input['foreground'] ?? '#111827', '#111827'),
                'accent' => $this->color($colors['accent'] ?? $input['accent'] ?? '#2563eb', '#2563eb'),
                'border' => $this->color($colors['border'] ?? $input['border'] ?? '#e5e7eb', '#e5e7eb'),
                'topbar_background' => $this->color($colors['topbar_background'] ?? $input['topbar_background'] ?? '#111827', '#111827'),
                'topbar_foreground' => $this->color($colors['topbar_foreground'] ?? $input['topbar_foreground'] ?? '#ffffff', '#ffffff'),
            ],
            'banner_gap' => [
                'desktop' => $this->clamp($input['banner_gap']['desktop'] ?? 0, [0, 120]),
                'mobile' => $this->clamp($input['banner_gap']['mobile'] ?? 0, [0, 80]),
            ],
            'menu_style' => [
                'primary' => $this->menuStyle($input['menu_style']['primary'] ?? null, 20),
                'mobile' => $this->menuStyle($input['menu_style']['mobile'] ?? null, 0),
            ],
            'custom' => [
                'container_width' => $this->clamp($custom['container_width'] ?? 1280, $bounds['container_width'] ?? [960, 1920]),
                'desktop_height' => $this->clamp($custom['desktop_height'] ?? 80, $bounds['header_height'] ?? [52, 120]),
                'tablet_height' => $this->clamp($custom['tablet_height'] ?? 72, $bounds['header_height'] ?? [52, 120]),
                'mobile_height' => $this->clamp($custom['mobile_height'] ?? 64, $bounds['header_height'] ?? [52, 120]),
                'topbar_height' => $this->clamp($custom['topbar_height'] ?? 32, $bounds['topbar_height'] ?? [24, 56]),
                'logo_max_height' => $this->clamp($custom['logo_max_height'] ?? 48, $bounds['logo_max_height'] ?? [24, 72]),
                'search_max_width' => $this->clamp($custom['search_max_width'] ?? 560, $bounds['search_max_width'] ?? [320, 900]),
            ],
        ];
    }

    /** Whitelisted values only: never render arbitrary CSS supplied by an admin form. */
    private function menuStyle(mixed $input, int $defaultGap): array
    {
        $input = is_array($input) ? $input : [];
        $fonts = ['inherit', 'Arial, sans-serif', 'Roboto, sans-serif', 'Inter, sans-serif', 'system-ui, sans-serif', 'Georgia, serif'];
        $weights = ['400', '500', '600', '700'];
        $effects = ['underline', 'color', 'background', 'none'];

        return [
            'font_family' => in_array($input['font_family'] ?? null, $fonts, true) ? $input['font_family'] : 'inherit',
            'font_size' => $this->clamp($input['font_size'] ?? 14, [12, 22]),
            'font_weight' => in_array((string) ($input['font_weight'] ?? '600'), $weights, true) ? (string) ($input['font_weight'] ?? '600') : '600',
            'item_gap' => $this->clamp($input['item_gap'] ?? $defaultGap, [0, 40]),
            'radius' => $this->clamp($input['radius'] ?? 8, [0, 24]),
            'text_color' => $this->color($input['text_color'] ?? '#0f172a', '#0f172a'),
            'hover_color' => $this->color($input['hover_color'] ?? '#047857', '#047857'),
            'active_color' => $this->color($input['active_color'] ?? '#047857', '#047857'),
            'hover_effect' => in_array($input['hover_effect'] ?? null, $effects, true) ? $input['hover_effect'] : 'underline',
        ];
    }

    private function clamp(mixed $value, array $bounds): int
    {
        $min = (int) ($bounds[0] ?? 0);
        $max = (int) ($bounds[1] ?? PHP_INT_MAX);

        return max($min, min($max, (int) $value));
    }

    private function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }
}
