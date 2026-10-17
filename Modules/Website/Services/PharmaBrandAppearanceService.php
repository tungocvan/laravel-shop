<?php

namespace Modules\Website\Services;

final class PharmaBrandAppearanceService
{
    public function resolve(mixed $input): array
    {
        $input = is_array($input) ? $input : [];
        $result = [
            'slogan' => mb_substr((string) ($input['slogan'] ?? 'Danh mục và thông tin dược phẩm'), 0, 180),
            'visibility' => [],
            'typography' => [],
        ];
        foreach (['logo', 'name', 'slogan'] as $part) {
            $value = $input['visibility'][$part] ?? 'both';
            $result['visibility'][$part] = in_array($value, ['both', 'desktop', 'mobile', 'hidden'], true) ? $value : 'both';
        }
        foreach (['desktop', 'mobile'] as $device) {
            foreach (['name', 'slogan'] as $part) {
                $style = $input['typography'][$device][$part] ?? [];
                $style = is_array($style) ? $style : [];
                $font = $style['font_family'] ?? 'inherit';
                $result['typography'][$device][$part] = [
                    'font_family' => in_array($font, ['inherit', 'Arial, sans-serif', 'Roboto, sans-serif', 'Inter, sans-serif', 'system-ui, sans-serif', 'Georgia, serif'], true) ? $font : 'inherit',
                    'font_size' => max(9, min(32, (int) ($style['font_size'] ?? ($part === 'name' ? ($device === 'desktop' ? 18 : 11) : ($device === 'desktop' ? 12 : 11))))),
                    'font_weight' => in_array((string) ($style['font_weight'] ?? ($part === 'name' ? '800' : '500')), ['400', '500', '600', '700', '800'], true) ? (string) ($style['font_weight'] ?? ($part === 'name' ? '800' : '500')) : '500',
                    'color' => $this->color($style['color'] ?? ($part === 'name' ? '#0f172a' : '#047857'), $part === 'name' ? '#0f172a' : '#047857'),
                ];
            }
        }
        return $result;
    }

    public function visible(array $appearance, string $part, string $device): bool
    {
        $value = $appearance['visibility'][$part] ?? 'both';
        return $value === 'both' || $value === $device;
    }

    private function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9a-fA-F]{6}$/', $value) ? strtolower($value) : $fallback;
    }
}
