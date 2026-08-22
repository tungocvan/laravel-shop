<?php

namespace Modules\Website\Services;

class FooterPresentationService
{
    public function resolve(?array $input = null): array
    {
        $defaults = (array) config('website.footer.presentation', []);
        $input = is_array($input) ? array_replace_recursive($defaults, $input) : $defaults;

        $containers = (array) config('website.footer.presets.container', []);
        $spacingPresets = (array) config('website.footer.presets.spacing', []);
        $gapPresets = (array) config('website.footer.presets.column_gap', []);
        $bounds = (array) config('website.footer.bounds', []);
        $globalColors = (array) config('website.design.colors', []);

        $mode = in_array($input['mode'] ?? null, ['basic', 'advanced'], true) ? $input['mode'] : 'basic';
        $container = array_key_exists($input['container'] ?? '', $containers) ? $input['container'] : 'standard';
        $spacing = array_key_exists($input['spacing'] ?? '', $spacingPresets) ? $input['spacing'] : 'comfortable';
        $columnGap = array_key_exists($input['column_gap'] ?? '', $gapPresets) ? $input['column_gap'] : 'comfortable';
        $inheritColors = (bool) ($input['inherit_colors'] ?? true);
        $custom = is_array($input['custom'] ?? null) ? $input['custom'] : [];
        $colors = is_array($input['colors'] ?? null) ? $input['colors'] : [];

        $containerWidth = $containers[$container] ?? 1280;
        $spacingValues = $spacingPresets[$spacing] ?? ['top' => 64, 'bottom' => 32, 'section' => 64];
        $gap = (int) ($gapPresets[$columnGap] ?? 48);

        if ($mode === 'advanced') {
            $containerWidth = $container === 'full'
                ? null
                : $this->clamp($custom['container_width'] ?? 1280, $bounds['container_width'] ?? [960, 1920]);
            $spacingValues = [
                'top' => $this->clamp($custom['padding_top'] ?? 64, $bounds['padding_top'] ?? [24, 120]),
                'bottom' => $this->clamp($custom['padding_bottom'] ?? 32, $bounds['padding_bottom'] ?? [16, 96]),
                'section' => $this->clamp($custom['section_gap'] ?? 64, $bounds['section_gap'] ?? [24, 120]),
            ];
            $gap = $this->clamp($custom['column_gap'] ?? 48, $bounds['column_gap'] ?? [16, 80]);
        }

        return [
            'mode' => $mode,
            'container' => $container,
            'container_width' => $containerWidth,
            'spacing' => $spacing,
            'padding_top' => (int) $spacingValues['top'],
            'padding_bottom' => (int) $spacingValues['bottom'],
            'section_gap' => (int) $spacingValues['section'],
            'column_gap' => $gap,
            'accent' => (bool) ($input['accent'] ?? true),
            'border' => (bool) ($input['border'] ?? true),
            'inherit_colors' => $inheritColors,
            'colors' => [
                'background' => $inheritColors
                    ? '#111827'
                    : $this->color($colors['background'] ?? '#111827', '#111827'),
                'foreground' => $inheritColors
                    ? '#9ca3af'
                    : $this->color($colors['foreground'] ?? '#9ca3af', '#9ca3af'),
                'heading' => $inheritColors
                    ? ($globalColors['surface'] ?? '#ffffff')
                    : $this->color($colors['heading'] ?? '#ffffff', '#ffffff'),
                'muted' => $inheritColors
                    ? ($globalColors['muted'] ?? '#6b7280')
                    : $this->color($colors['muted'] ?? '#6b7280', '#6b7280'),
                'accent' => $inheritColors
                    ? ($globalColors['primary'] ?? '#2563eb')
                    : $this->color($colors['accent'] ?? '#2563eb', '#2563eb'),
                'border' => $inheritColors
                    ? '#1f2937'
                    : $this->color($colors['border'] ?? '#1f2937', '#1f2937'),
            ],
            'custom' => [
                'container_width' => $this->clamp($custom['container_width'] ?? 1280, $bounds['container_width'] ?? [960, 1920]),
                'padding_top' => $this->clamp($custom['padding_top'] ?? 64, $bounds['padding_top'] ?? [24, 120]),
                'padding_bottom' => $this->clamp($custom['padding_bottom'] ?? 32, $bounds['padding_bottom'] ?? [16, 96]),
                'column_gap' => $this->clamp($custom['column_gap'] ?? 48, $bounds['column_gap'] ?? [16, 80]),
                'section_gap' => $this->clamp($custom['section_gap'] ?? 64, $bounds['section_gap'] ?? [24, 120]),
                'logo_max_height' => $this->clamp($custom['logo_max_height'] ?? 40, $bounds['logo_max_height'] ?? [24, 72]),
                'social_icon_size' => $this->clamp($custom['social_icon_size'] ?? 40, $bounds['social_icon_size'] ?? [32, 56]),
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
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
            ? strtolower($value)
            : $fallback;
    }
}
