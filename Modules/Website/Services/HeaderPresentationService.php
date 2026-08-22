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
                'background' => $this->color($input['background'] ?? '#ffffff', '#ffffff'),
                'foreground' => $this->color($input['foreground'] ?? '#111827', '#111827'),
                'accent' => $this->color($input['accent'] ?? '#2563eb', '#2563eb'),
                'border' => $this->color($input['border'] ?? '#e5e7eb', '#e5e7eb'),
                'topbar_background' => $this->color($input['topbar_background'] ?? '#111827', '#111827'),
                'topbar_foreground' => $this->color($input['topbar_foreground'] ?? '#ffffff', '#ffffff'),
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
