<?php

namespace Modules\Website\Services;

class WebsiteShellService
{
    public function defaults(): array
    {
        return [
            'header_enabled' => true,
            'homepage_enabled' => true,
            'footer_enabled' => true,
            'maintenance' => [
                'enabled' => false,
                'title' => 'Website đang được bảo trì',
                'message' => 'Chúng tôi đang cập nhật hệ thống để phục vụ bạn tốt hơn. Vui lòng quay lại sau.',
            ],
        ];
    }

    public function resolve(?array $saved = null): array
    {
        $defaults = $this->defaults();
        $saved = is_array($saved) ? $saved : [];
        $maintenance = is_array($saved['maintenance'] ?? null) ? $saved['maintenance'] : [];

        return [
            'header_enabled' => (bool) ($saved['header_enabled'] ?? $defaults['header_enabled']),
            'homepage_enabled' => (bool) ($saved['homepage_enabled'] ?? $defaults['homepage_enabled']),
            'footer_enabled' => (bool) ($saved['footer_enabled'] ?? $defaults['footer_enabled']),
            'maintenance' => [
                'enabled' => (bool) ($maintenance['enabled'] ?? $defaults['maintenance']['enabled']),
                'title' => $this->plainText($maintenance['title'] ?? null, $defaults['maintenance']['title'], 120),
                'message' => $this->plainText($maintenance['message'] ?? null, $defaults['maintenance']['message'], 1000),
            ],
        ];
    }

    private function plainText(mixed $value, string $default, int $maxLength): string
    {
        if (! is_string($value)) {
            return $default;
        }

        $value = trim(strip_tags($value));

        return $value !== '' && mb_strlen($value) <= $maxLength ? $value : $default;
    }
}
