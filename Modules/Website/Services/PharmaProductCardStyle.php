<?php

namespace Modules\Website\Services;

class PharmaProductCardStyle
{
    public function normalize(array $input): array
    {
        $preset = in_array($input['preset'] ?? '', ['classic', 'modern', 'compact'], true) ? $input['preset'] : 'classic';
        $defaults = [
            'preset' => $preset, 'image_ratio' => '4/3', 'image_fit' => 'contain',
            'radius' => $preset === 'compact' ? 10 : 16, 'padding' => $preset === 'compact' ? 12 : 20,
            'gap' => 20, 'desktop_columns' => 4, 'tablet_columns' => 2, 'mobile_columns' => 1,
            'title_size' => 18, 'title_lines' => 2, 'shadow' => $preset === 'modern',
            'show_category' => true, 'show_action' => true, 'action_label' => 'Xem thông tin',
            'background' => '#ffffff', 'image_background' => '#ecfdf5',
            'text_color' => '#142238', 'muted_color' => '#047857',
            'border_color' => '#e2e8f0', 'action_color' => '#047857',
        ];
        $style = array_replace($defaults, array_intersect_key($input, $defaults));
        foreach (['radius' => [0,32], 'padding' => [8,32], 'gap' => [8,40], 'title_size' => [12,26], 'title_lines' => [1,4]] as $key => [$min,$max]) {
            $style[$key] = max($min, min($max, (int) $style[$key]));
        }
        foreach (['desktop_columns' => [2,3,4,5], 'tablet_columns' => [1,2,3], 'mobile_columns' => [1,2]] as $key => $allowed) {
            $style[$key] = in_array((int) $style[$key], $allowed, true) ? (int) $style[$key] : $defaults[$key];
        }
        foreach (['background','image_background','text_color','muted_color','border_color','action_color'] as $key) {
            $style[$key] = is_string($style[$key]) && preg_match('/^#[0-9a-fA-F]{6}$/D', $style[$key]) ? $style[$key] : $defaults[$key];
        }
        $style['image_ratio'] = in_array($style['image_ratio'], ['1/1','4/3','3/4'], true) ? $style['image_ratio'] : '4/3';
        $style['image_fit'] = in_array($style['image_fit'], ['contain','cover'], true) ? $style['image_fit'] : 'contain';
        $style['action_label'] = mb_substr(trim((string) $style['action_label']), 0, 60) ?: 'Xem thông tin';
        foreach (['shadow','show_category','show_action'] as $key) $style[$key] = filter_var($style[$key], FILTER_VALIDATE_BOOLEAN);
        return $style;
    }
}
