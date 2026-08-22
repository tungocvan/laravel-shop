<?php

namespace Modules\Website\Services;

class WebsiteAppearanceService
{
    public function defaults(string $siteName = 'FlexBiz'): array
    {
        return [
            'application_name' => $siteName,
            'apple_title' => $siteName,
            'theme_color' => '#0f172a',
            'background_color' => '#ffffff',
            'apple_status_bar_style' => 'default',
            'manifest_enabled' => true,
            'service_worker_enabled' => true,
        ];
    }

    public function resolve(?array $saved = null, string $siteName = 'FlexBiz'): array
    {
        $defaults = $this->defaults($siteName);
        $saved = is_array($saved) ? $saved : [];

        return [
            'application_name' => $this->plainText($saved['application_name'] ?? null, $defaults['application_name'], 120),
            'apple_title' => $this->plainText($saved['apple_title'] ?? null, $defaults['apple_title'], 60),
            'theme_color' => $this->color($saved['theme_color'] ?? null, $defaults['theme_color']),
            'background_color' => $this->color($saved['background_color'] ?? null, $defaults['background_color']),
            'apple_status_bar_style' => $this->choice($saved['apple_status_bar_style'] ?? null, ['default', 'black', 'black-translucent'], $defaults['apple_status_bar_style']),
            'manifest_enabled' => (bool) ($saved['manifest_enabled'] ?? $defaults['manifest_enabled']),
            'service_worker_enabled' => (bool) ($saved['service_worker_enabled'] ?? $defaults['service_worker_enabled']),
        ];
    }

    private function color(mixed $value, string $default): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value)
            ? strtolower($value)
            : $default;
    }

    private function plainText(mixed $value, string $default, int $maxLength): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $value = trim(strip_tags($value));

        return $value !== '' && mb_strlen($value) <= $maxLength ? $value : $default;
    }

    private function choice(mixed $value, array $allowed, string $default): string
    {
        return is_string($value) && in_array($value, $allowed, true) ? $value : $default;
    }
}
