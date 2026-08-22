<?php

namespace Modules\Website\Services;

class WebsiteLayoutPresentationService
{
    public function defaults(): array
    {
        return [
            'body' => [
                'background' => 'background',
            ],
            'main' => [
                'container' => 'full',
                'background' => 'transparent',
                'alignment' => 'center',
                'desktop' => [
                    'padding_top' => 32,
                    'padding_bottom' => 32,
                    'padding_x' => 0,
                ],
                'mobile' => [
                    'padding_top' => 32,
                    'padding_bottom' => 32,
                    'padding_x' => 0,
                ],
            ],
            'scroll' => [
                'smooth' => false,
            ],
        ];
    }

    public function resolve(?array $saved = null): array
    {
        $defaults = $this->defaults();
        $saved = is_array($saved) ? $saved : [];

        return [
            'body' => [
                'background' => $this->choice(
                    data_get($saved, 'body.background'),
                    ['background', 'surface'],
                    $defaults['body']['background'],
                ),
            ],
            'main' => [
                'container' => $this->choice(
                    data_get($saved, 'main.container'),
                    ['full', 'wide', 'standard', 'compact'],
                    $defaults['main']['container'],
                ),
                'background' => $this->choice(
                    data_get($saved, 'main.background'),
                    ['transparent', 'background', 'surface'],
                    $defaults['main']['background'],
                ),
                'alignment' => $this->choice(
                    data_get($saved, 'main.alignment'),
                    ['left', 'center'],
                    $defaults['main']['alignment'],
                ),
                'desktop' => $this->spacing(data_get($saved, 'main.desktop'), $defaults['main']['desktop']),
                'mobile' => $this->spacing(data_get($saved, 'main.mobile'), $defaults['main']['mobile']),
            ],
            'scroll' => [
                'smooth' => (bool) data_get($saved, 'scroll.smooth', $defaults['scroll']['smooth']),
            ],
        ];
    }

    public function cssVariables(array $presentation): string
    {
        $resolved = $this->resolve($presentation);
        $desktop = $resolved['main']['desktop'];
        $mobile = $resolved['main']['mobile'];

        return implode('; ', [
            '--website-main-padding-top: '.$desktop['padding_top'].'px',
            '--website-main-padding-bottom: '.$desktop['padding_bottom'].'px',
            '--website-main-padding-x: '.$desktop['padding_x'].'px',
            '--website-main-mobile-padding-top: '.$mobile['padding_top'].'px',
            '--website-main-mobile-padding-bottom: '.$mobile['padding_bottom'].'px',
            '--website-main-mobile-padding-x: '.$mobile['padding_x'].'px',
        ]);
    }

    public function containerMaxWidth(array $presentation, array $design): string
    {
        $resolved = $this->resolve($presentation);
        $container = $resolved['main']['container'];

        if ($container === 'full') {
            return 'none';
        }

        return (string) data_get($design, 'layout.container_width.'.$container, 'none');
    }

    private function spacing(mixed $saved, array $defaults): array
    {
        $saved = is_array($saved) ? $saved : [];

        return [
            'padding_top' => $this->integer($saved['padding_top'] ?? null, $defaults['padding_top'], 0, 160),
            'padding_bottom' => $this->integer($saved['padding_bottom'] ?? null, $defaults['padding_bottom'], 0, 160),
            'padding_x' => $this->integer($saved['padding_x'] ?? null, $defaults['padding_x'], 0, 96),
        ];
    }

    private function integer(mixed $value, int $default, int $min, int $max): int
    {
        if (! is_numeric($value)) {
            return $default;
        }

        $value = (int) $value;

        return $value >= $min && $value <= $max ? $value : $default;
    }

    private function choice(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }
}
