<?php

namespace Modules\Website\Services;

final class PharmaHeroService
{
    public function defaults(): array
    {
        return [
            'badge' => 'Thông tin dược phẩm',
            'title' => 'Khám phá danh mục sản phẩm dược phẩm',
            'description' => 'Tra cứu sản phẩm và nhóm dược phẩm từ danh mục được công bố, cập nhật theo hệ thống.',
            'button_text' => 'Tra cứu sản phẩm',
            'button_url' => '/product',
            'caption' => 'Danh mục · Hoạt chất · Thông tin sản phẩm',
            'show_badge' => true,
            'show_button' => true,
            'show_caption' => true,
            'show_illustration' => true,
            'illustration_type' => 'default',
            'illustration_image' => '',
            'desktop_width' => 1280,
            'mobile_width' => 100,
            'desktop_min_height' => 0,
            'mobile_min_height' => 0,
            'desktop_illustration_size' => 320,
            'mobile_illustration_size' => 220,
            'background_start' => '#073b48',
            'background_end' => '#0c6870',
            'text_color' => '#ffffff',
            'button_background' => '#ffffff',
            'button_color' => '#047857',
            'title_font' => 'inherit',
            'desktop_title_size' => 60,
            'mobile_title_size' => 36,
            'desktop_padding' => 96,
            'mobile_padding' => 64,
        ];
    }

    public function normalize(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $defaults = $this->defaults();
        $result = $defaults;
        foreach (['badge' => 60, 'title' => 160, 'description' => 400, 'button_text' => 80, 'caption' => 120] as $field => $max) {
            $result[$field] = mb_substr(trim(strip_tags((string) ($input[$field] ?? $defaults[$field]))), 0, $max);
        }
        $type = $input['illustration_type'] ?? 'default';
        $result['illustration_type'] = in_array($type, ['default', 'image', 'none'], true) ? $type : 'default';
        $image = trim((string) ($input['illustration_image'] ?? ''));
        $result['illustration_image'] = preg_match('~^pharma/hero/[a-zA-Z0-9_.-]+\\.(?:png|jpe?g|webp|gif)$~i', $image) ? $image : '';
        $url = trim((string) ($input['button_url'] ?? $defaults['button_url']));
        $result['button_url'] = preg_match('~^/(?!/)[a-zA-Z0-9/_?#=&%.+-]*$~', $url) ? $url : $defaults['button_url'];
        foreach (['show_badge', 'show_button', 'show_caption', 'show_illustration'] as $field) {
            $result[$field] = filter_var($input[$field] ?? $defaults[$field], FILTER_VALIDATE_BOOLEAN);
        }
        foreach (['background_start', 'background_end', 'text_color', 'button_background', 'button_color'] as $field) {
            $value = $input[$field] ?? $defaults[$field];
            $result[$field] = is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $defaults[$field];
        }
        $fonts = ['inherit', 'Arial, sans-serif', 'Roboto, sans-serif', 'Inter, sans-serif', 'system-ui, sans-serif', 'Georgia, serif'];
        $result['title_font'] = in_array($input['title_font'] ?? null, $fonts, true) ? $input['title_font'] : 'inherit';
        foreach (['desktop_title_size' => [28, 80], 'mobile_title_size' => [20, 52], 'desktop_padding' => [0, 160], 'mobile_padding' => [0, 120], 'desktop_width' => [720, 1920], 'mobile_width' => [80, 100], 'desktop_min_height' => [0, 900], 'mobile_min_height' => [0, 700], 'desktop_illustration_size' => [120, 600], 'mobile_illustration_size' => [80, 400]] as $field => [$min, $max]) {
            $result[$field] = max($min, min($max, (int) ($input[$field] ?? $defaults[$field])));
        }
        return $result;
    }
}
