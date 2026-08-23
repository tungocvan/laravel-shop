<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminShellPresentationService
{
    private const SPACING_REM = [
        '0' => '0rem',
        '1' => '0.25rem',
        '2' => '0.5rem',
        '3' => '0.75rem',
        '4' => '1rem',
        '5' => '1.25rem',
        '6' => '1.5rem',
        '8' => '2rem',
        '10' => '2.5rem',
        '12' => '3rem',
    ];

    public function __construct(
        private readonly AdminLayoutManager $layoutManager,
    ) {
    }

    public function context(): array
    {
        $config = $this->layoutManager->config();
        $container = (string) data_get($config, 'layout.container', 'screen-2xl');
        $density = (string) data_get($config, 'layout.density', 'comfortable');

        return [
            'container' => $container,
            'density' => $density,
            'content_class' => $this->containerClass($container),
            'content_padding_class' => '',
            'content_style' => $this->contentStyle($config),
            'shell_style' => $this->shellStyle($config),
            'reduced_motion' => (bool) data_get($config, 'layout.behavior.reduced_motion', true),
            'sidebar_expanded_width' => (string) data_get($config, 'sidebar.expanded_width', '16rem'),
            'sidebar_collapsed_width' => (string) data_get($config, 'sidebar.collapsed_width', '5rem'),
            'header_height' => (string) data_get($config, 'header.height', '4rem'),
        ];
    }

    private function containerClass(string $container): string
    {
        return match ($container) {
            'full' => 'w-full max-w-none',
            'narrow' => 'w-full lg:w-4/5 max-w-5xl mx-auto',
            '7xl' => 'w-full lg:w-11/12 max-w-7xl mx-auto',
            default => 'w-full lg:w-11/12 2xl:w-full max-w-screen-2xl mx-auto',
        };
    }

    private function contentStyle(array $config): string
    {
        $desktopX = $this->space(data_get($config, 'layout.spacing.content_padding_x', '6'));
        $top = $this->space(data_get($config, 'layout.spacing.content_padding_top', '6'));
        $bottom = $this->space(data_get($config, 'layout.spacing.content_padding_bottom', '8'));
        $tabletX = $this->space(data_get($config, 'layout.spacing.tablet_padding_x', '5'));
        $mobileX = $this->space(data_get($config, 'layout.spacing.mobile_padding_x', '4'));
        $sectionGap = $this->space(data_get($config, 'layout.spacing.section_gap', '6'));
        $surface = $this->surface(data_get($config, 'layout.surface.content_surface', 'transparent'));
        $border = data_get($config, 'layout.surface.border', 'system') === 'none' ? 'transparent' : 'var(--admin-border-subtle)';
        $radius = $this->radius(data_get($config, 'layout.surface.radius', 'lg'));

        return implode('; ', [
            "--admin-content-padding-x: {$desktopX}",
            "--admin-content-padding-x-tablet: {$tabletX}",
            "--admin-content-padding-x-mobile: {$mobileX}",
            "--admin-content-padding-top: {$top}",
            "--admin-content-padding-bottom: {$bottom}",
            "--admin-section-gap: {$sectionGap}",
            "--admin-content-surface: {$surface}",
            "--admin-layout-border: {$border}",
            "--admin-layout-radius: {$radius}",
        ]);
    }

    private function shellStyle(array $config): string
    {
        $background = $this->surface(data_get($config, 'layout.surface.page_background', 'system'));

        return "--admin-page-background: {$background}";
    }

    private function surface(mixed $value): string
    {
        return match ((string) $value) {
            'white' => '#ffffff',
            'slate-50' => '#f8fafc',
            'transparent' => 'transparent',
            default => 'var(--admin-surface-base)',
        };
    }

    private function radius(mixed $value): string
    {
        return match ((string) $value) {
            'none' => '0rem',
            'sm' => '0.25rem',
            'md' => '0.375rem',
            default => '0.5rem',
        };
    }

    private function space(mixed $value): string
    {
        return self::SPACING_REM[(string) $value] ?? self::SPACING_REM['6'];
    }
}
