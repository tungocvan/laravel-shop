<?php

namespace Modules\Website\database\Seeders;

use Illuminate\Database\Seeder;
use Modules\System\Services\SettingsService;
use Modules\Website\Services\FooterPresentationService;
use Modules\Website\Services\HeaderPresentationService;

class LayoutThemeSeeder extends Seeder
{
    /**
     * Seed 3 Header Layout Themes and 3 Footer Layout Themes for UI testing.
     *
     * php artisan db:seed --class="Modules\\Website\\database\\Seeders\\LayoutThemeSeeder"
     */
    public function run(): void
    {
        $settings = app(SettingsService::class);
        $headerPresentation = app(HeaderPresentationService::class);
        $footerPresentation = app(FooterPresentationService::class);

        $headerThemes = is_array($settings->get('header.layout_themes', []))
            ? $settings->get('header.layout_themes', [])
            : [];
        $footerThemes = is_array($settings->get('footer.layout_themes', []))
            ? $settings->get('footer.layout_themes', [])
            : [];

        foreach ($this->headerThemes($headerPresentation) as $slug => $theme) {
            $headerThemes[$slug] = $theme;
        }

        foreach ($this->footerThemes($footerPresentation) as $slug => $theme) {
            $footerThemes[$slug] = $theme;
        }

        $settings->updateMany([
            'header.layout_themes' => $headerThemes,
        ], 'header');

        $settings->updateMany([
            'footer.layout_themes' => $footerThemes,
        ], 'footer');

        $this->command?->info('Seeded 3 Header Layout Themes and 3 Footer Layout Themes. Existing custom themes were preserved.');
    }

    private function headerThemes(HeaderPresentationService $presentation): array
    {
        return [
            'demo-header-classic' => $this->theme(
                'Classic Commerce',
                $this->headerLayout('left', 'center', 'right'),
                $presentation->resolve([
                    'mode' => 'basic',
                    'container' => 'standard',
                    'size' => 'normal',
                    'sticky' => true,
                    'shadow' => 'soft',
                    'inherit_colors' => true,
                ])
            ),
            'demo-header-search-first' => $this->theme(
                'Search First',
                $this->headerLayout('left', 'right', 'center'),
                $presentation->resolve([
                    'mode' => 'advanced',
                    'container' => 'wide',
                    'size' => 'comfortable',
                    'sticky' => true,
                    'shadow' => 'medium',
                    'inherit_colors' => true,
                    'custom' => [
                        'container_width' => 1440,
                        'desktop_height' => 92,
                        'tablet_height' => 80,
                        'mobile_height' => 68,
                        'topbar_height' => 32,
                        'logo_max_height' => 44,
                        'search_max_width' => 760,
                    ],
                ])
            ),
            'demo-header-minimal' => $this->theme(
                'Minimal Clean',
                [
                    'desktop' => [
                        'topbar' => [['type' => 'topbar', 'enabled' => false]],
                        'main' => [
                            'left' => [['type' => 'brand', 'enabled' => true]],
                            'center' => [['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'desktop']]],
                            'right' => [['type' => 'actions', 'enabled' => true]],
                        ],
                    ],
                    'mobile' => [
                        'search' => [['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'mobile']]],
                        'drawer' => [['type' => 'mobile-menu', 'enabled' => true]],
                    ],
                ],
                $presentation->resolve([
                    'mode' => 'advanced',
                    'container' => 'standard',
                    'size' => 'compact',
                    'sticky' => true,
                    'shadow' => 'none',
                    'inherit_colors' => true,
                    'custom' => [
                        'container_width' => 1200,
                        'desktop_height' => 64,
                        'tablet_height' => 60,
                        'mobile_height' => 56,
                        'topbar_height' => 24,
                        'logo_max_height' => 36,
                        'search_max_width' => 520,
                    ],
                ])
            ),
        ];
    }

    private function headerLayout(string $brandSlot, string $searchSlot, string $actionsSlot): array
    {
        $main = ['left' => [], 'center' => [], 'right' => []];
        $main[$brandSlot][] = ['type' => 'brand', 'enabled' => true];
        $main[$searchSlot][] = ['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'desktop']];
        $main[$actionsSlot][] = ['type' => 'actions', 'enabled' => true];

        return [
            'desktop' => [
                'topbar' => [['type' => 'topbar', 'enabled' => true]],
                'main' => $main,
            ],
            'mobile' => [
                'search' => [['type' => 'search', 'enabled' => true, 'config' => ['mode' => 'mobile']]],
                'drawer' => [['type' => 'mobile-menu', 'enabled' => true]],
            ],
        ];
    }

    private function footerThemes(FooterPresentationService $presentation): array
    {
        $standardLayout = (array) config('website.footer.layout', []);

        return [
            'demo-footer-commerce' => $this->theme(
                'Commerce Standard',
                $standardLayout,
                $presentation->resolve([
                    'mode' => 'basic',
                    'container' => 'standard',
                    'spacing' => 'comfortable',
                    'column_gap' => 'comfortable',
                    'inherit_colors' => true,
                    'accent' => true,
                    'border' => true,
                ])
            ),
            'demo-footer-compact' => $this->theme(
                'Compact Business',
                $standardLayout,
                $presentation->resolve([
                    'mode' => 'advanced',
                    'container' => 'standard',
                    'spacing' => 'compact',
                    'column_gap' => 'compact',
                    'inherit_colors' => true,
                    'accent' => true,
                    'border' => true,
                    'custom' => [
                        'container_width' => 1200,
                        'padding_top' => 40,
                        'padding_bottom' => 24,
                        'column_gap' => 24,
                        'section_gap' => 40,
                        'logo_max_height' => 36,
                        'social_icon_size' => 36,
                    ],
                ])
            ),
            'demo-footer-brand-focus' => $this->theme(
                'Brand Focus',
                [
                    'desktop' => [
                        'top' => [],
                        'main' => [
                            'brand' => [
                                ['type' => 'brand', 'enabled' => true],
                                ['type' => 'contact', 'enabled' => true],
                            ],
                            'columns' => [['type' => 'menu-columns', 'enabled' => true]],
                            'extra' => [
                                ['type' => 'social-links', 'enabled' => true],
                                ['type' => 'app-install', 'enabled' => true],
                            ],
                        ],
                        'bottom' => [
                            'left' => [
                                ['type' => 'copyright', 'enabled' => true],
                                ['type' => 'legal-links', 'enabled' => true],
                            ],
                            'right' => [['type' => 'trust-badges', 'enabled' => true]],
                        ],
                    ],
                    'mobile' => [
                        'main' => [
                            ['type' => 'brand', 'enabled' => true],
                            ['type' => 'social-links', 'enabled' => true],
                            ['type' => 'contact', 'enabled' => true],
                            ['type' => 'menu-columns', 'enabled' => true],
                            ['type' => 'app-install', 'enabled' => true],
                        ],
                        'bottom' => [
                            ['type' => 'copyright', 'enabled' => true],
                            ['type' => 'legal-links', 'enabled' => true],
                            ['type' => 'trust-badges', 'enabled' => true],
                        ],
                    ],
                    'overlay' => [['type' => 'back-to-top', 'enabled' => true]],
                ],
                $presentation->resolve([
                    'mode' => 'advanced',
                    'container' => 'wide',
                    'spacing' => 'comfortable',
                    'column_gap' => 'normal',
                    'inherit_colors' => true,
                    'accent' => true,
                    'border' => true,
                    'custom' => [
                        'container_width' => 1440,
                        'padding_top' => 72,
                        'padding_bottom' => 36,
                        'column_gap' => 36,
                        'section_gap' => 72,
                        'logo_max_height' => 48,
                        'social_icon_size' => 44,
                    ],
                ])
            ),
        ];
    }

    private function theme(string $name, array $layout, array $presentation): array
    {
        return [
            'version' => 1,
            'name' => $name,
            'layout' => $layout,
            'presentation' => $presentation,
            'updated_at' => now()->toIso8601String(),
        ];
    }
}
