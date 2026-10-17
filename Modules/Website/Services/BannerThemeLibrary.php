<?php

namespace Modules\Website\Services;

/**
 * Reusable presentation-only banner themes. Content and uploaded images stay
 * owned by the consuming homepage template.
 */
final class BannerThemeLibrary
{
    public function all(): array
    {
        return [
            'pharma-gradient' => [
                'name' => 'Pharma Gradient',
                'description' => 'Nền xanh đậm, minh họa dược phẩm',
                'background_start' => '#073b48', 'background_end' => '#0c6870',
                'text_color' => '#ffffff', 'button_background' => '#ffffff', 'button_color' => '#047857',
                'title_font' => 'inherit', 'desktop_title_size' => 60, 'mobile_title_size' => 36,
                'desktop_padding' => 96, 'mobile_padding' => 64,
            ],
            'pharma-light' => [
                'name' => 'Pharma Light',
                'description' => 'Nền sáng, chữ xanh đậm',
                'background_start' => '#effcf7', 'background_end' => '#c7f4e2',
                'text_color' => '#064e3b', 'button_background' => '#047857', 'button_color' => '#ffffff',
                'title_font' => 'inherit', 'desktop_title_size' => 56, 'mobile_title_size' => 34,
                'desktop_padding' => 88, 'mobile_padding' => 56,
            ],
            'pharma-corporate' => [
                'name' => 'Pharma Corporate',
                'description' => 'Xanh navy, phong cách doanh nghiệp',
                'background_start' => '#102a43', 'background_end' => '#176b82',
                'text_color' => '#ffffff', 'button_background' => '#e0f2fe', 'button_color' => '#0c4a6e',
                'title_font' => 'Arial, sans-serif', 'desktop_title_size' => 54, 'mobile_title_size' => 32,
                'desktop_padding' => 100, 'mobile_padding' => 64,
            ],
            'pharma-split' => [
                'name' => 'Pharma Split',
                'description' => 'Bố cục rộng, nội dung trái và minh họa phải',
                'background_start' => '#065f46', 'background_end' => '#0f766e',
                'text_color' => '#ffffff', 'button_background' => '#ffffff', 'button_color' => '#065f46',
                'title_font' => 'system-ui, sans-serif', 'desktop_title_size' => 48, 'mobile_title_size' => 30,
                'desktop_padding' => 72, 'mobile_padding' => 48,
            ],
        ];
    }

    public function apply(string $id, array $current, PharmaHeroService $hero): ?array
    {
        $themes = $this->all();
        if (! isset($themes[$id])) {
            return null;
        }
        $theme = $themes[$id];
        unset($theme['name'], $theme['description']);

        // Preserve all text, links, visibility, images and custom dimensions.
        return $hero->normalize(array_replace($current, $theme));
    }
}
