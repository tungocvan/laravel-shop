<?php

namespace Modules\Website\Services;

class HomepagePresentationService
{
    public function defaults(): array
    {
        return [
            'mode' => 'basic',
            'container' => 'standard',
            'spacing' => 'normal',
            'custom' => [
                'container_width' => 1280,
                'page_padding' => 16,
                'section_gap' => 48,
                'mobile_section_gap' => 32,
            ],
        ];
    }

    public function resolve(mixed $value): array
    {
        $value = is_array($value) ? $value : [];
        $defaults = $this->defaults();
        $custom = array_merge($defaults['custom'], is_array($value['custom'] ?? null) ? $value['custom'] : []);

        return [
            'mode' => in_array($value['mode'] ?? null, ['basic', 'advanced'], true) ? $value['mode'] : $defaults['mode'],
            'container' => in_array($value['container'] ?? null, ['standard', 'wide', 'full'], true) ? $value['container'] : $defaults['container'],
            'spacing' => in_array($value['spacing'] ?? null, ['compact', 'normal', 'comfortable'], true) ? $value['spacing'] : $defaults['spacing'],
            'custom' => [
                'container_width' => $this->integer($custom['container_width'] ?? null, 960, 1920, 1280),
                'page_padding' => $this->integer($custom['page_padding'] ?? null, 0, 64, 16),
                'section_gap' => $this->integer($custom['section_gap'] ?? null, 16, 120, 48),
                'mobile_section_gap' => $this->integer($custom['mobile_section_gap'] ?? null, 12, 96, 32),
            ],
        ];
    }

    public function containerClass(array $presentation): string
    {
        return match ($presentation['container'] ?? 'standard') {
            'full' => 'w-full',
            'wide' => 'mx-auto w-full max-w-[1440px]',
            default => 'mx-auto w-full max-w-7xl',
        };
    }

    public function inlineStyle(array $presentation): string
    {
        $resolved = $this->resolve($presentation);
        $custom = $resolved['custom'];

        $gap = match ($resolved['spacing']) {
            'compact' => 32,
            'comfortable' => 64,
            default => 48,
        };

        if ($resolved['mode'] === 'advanced') {
            $gap = $custom['section_gap'];
        }

        $maxWidth = $resolved['container'] === 'full'
            ? 'none'
            : ($resolved['mode'] === 'advanced' ? $custom['container_width'].'px' : null);

        return collect([
            '--homepage-section-gap: '.$gap.'px',
            '--homepage-mobile-section-gap: '.$custom['mobile_section_gap'].'px',
            '--homepage-page-padding: '.$custom['page_padding'].'px',
            $maxWidth ? 'max-width: '.$maxWidth : null,
        ])->filter()->implode('; ');
    }

    private function integer(mixed $value, int $min, int $max, int $default): int
    {
        $value = filter_var($value, FILTER_VALIDATE_INT);

        return $value === false ? $default : max($min, min($max, $value));
    }
}
