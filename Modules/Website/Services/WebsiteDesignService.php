<?php

namespace Modules\Website\Services;

class WebsiteDesignService
{
    public function defaults(): array
    {
        return (array) config('website.design', []);
    }

    public function resolve(?array $saved = null): array
    {
        $defaults = $this->defaults();
        $saved = is_array($saved) ? $saved : [];

        return [
            'typography' => [
                'font_family_body' => $this->fontFamily(data_get($saved, 'typography.font_family_body'), data_get($defaults, 'typography.font_family_body', 'ui-sans-serif, system-ui, sans-serif')),
                'font_family_heading' => $this->fontFamily(data_get($saved, 'typography.font_family_heading'), data_get($defaults, 'typography.font_family_heading', 'ui-sans-serif, system-ui, sans-serif')),
                'font_family_mono' => $this->fontFamily(data_get($saved, 'typography.font_family_mono'), data_get($defaults, 'typography.font_family_mono', 'ui-monospace, monospace')),
                'base_font_size' => $this->cssLength(data_get($saved, 'typography.base_font_size'), data_get($defaults, 'typography.base_font_size', '16px'), 10, 24),
                'line_height' => $this->mapNumeric(data_get($saved, 'typography.line_height'), data_get($defaults, 'typography.line_height', []), 1, 2.5),
                'font_size' => $this->mapLengths(data_get($saved, 'typography.font_size'), data_get($defaults, 'typography.font_size', []), 8, 64),
            ],
            'colors' => $this->mapColors(data_get($saved, 'colors'), data_get($defaults, 'colors', [])),
            'layout' => [
                'container_width' => $this->mapContainerWidths(data_get($saved, 'layout.container_width'), data_get($defaults, 'layout.container_width', [])),
                'default_container' => $this->choice(data_get($saved, 'layout.default_container'), ['compact', 'standard', 'wide', 'full'], data_get($defaults, 'layout.default_container', 'standard')),
                'radius' => $this->mapLengths(data_get($saved, 'layout.radius'), data_get($defaults, 'layout.radius', []), 0, 32),
                'shadow' => (array) data_get($defaults, 'layout.shadow', []),
            ],
        ];
    }

    private function mapColors(mixed $saved, array $defaults): array
    {
        $saved = is_array($saved) ? $saved : [];
        foreach ($defaults as $key => $default) {
            $value = $saved[$key] ?? null;
            $defaults[$key] = is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $default;
        }
        return $defaults;
    }

    private function mapLengths(mixed $saved, array $defaults, float $min, float $max): array
    {
        $saved = is_array($saved) ? $saved : [];
        foreach ($defaults as $key => $default) {
            $defaults[$key] = $this->cssLength($saved[$key] ?? null, $default, $min, $max);
        }
        return $defaults;
    }

    private function mapNumeric(mixed $saved, array $defaults, float $min, float $max): array
    {
        $saved = is_array($saved) ? $saved : [];
        foreach ($defaults as $key => $default) {
            $value = $saved[$key] ?? null;
            $defaults[$key] = is_numeric($value) && (float) $value >= $min && (float) $value <= $max ? (string) $value : (string) $default;
        }
        return $defaults;
    }

    private function mapContainerWidths(mixed $saved, array $defaults): array
    {
        $saved = is_array($saved) ? $saved : [];
        foreach ($defaults as $key => $default) {
            if ($key === 'full') {
                $defaults[$key] = '100%';
                continue;
            }
            $defaults[$key] = $this->cssLength($saved[$key] ?? null, $default, 640, 1920);
        }
        return $defaults;
    }

    private function cssLength(mixed $value, string $default, float $min, float $max): string
    {
        if (! is_string($value) || ! preg_match('/^(\d+(?:\.\d+)?)(px|rem)$/', trim($value), $matches)) {
            return $default;
        }
        $number = (float) $matches[1];
        $px = $matches[2] === 'rem' ? $number * 16 : $number;
        return $px >= $min && $px <= $max ? trim($value) : $default;
    }

    private function fontFamily(mixed $value, string $default): string
    {
        if (! is_string($value) || $value === '' || strlen($value) > 240 || preg_match('/[;{}<>]/', $value)) {
            return $default;
        }
        return trim($value);
    }

    private function choice(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }
}
