<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminShellPresentationService
{
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
            'content_padding_class' => $this->densityClass($density),
            'sidebar_expanded_width' => (string) data_get($config, 'sidebar.expanded_width', '16rem'),
            'sidebar_collapsed_width' => (string) data_get($config, 'sidebar.collapsed_width', '5rem'),
            'header_height' => (string) data_get($config, 'header.height', '4rem'),
        ];
    }

    private function containerClass(string $container): string
    {
        return match ($container) {
            // Full intentionally consumes all available main-column width.
            'full' => 'w-full max-w-none',

            // Narrow is optimized for settings/forms/readability and should be
            // visibly narrower even before the viewport reaches its max-width.
            'narrow' => 'w-full lg:w-4/5 max-w-5xl mx-auto',

            // 7xl is the balanced/default constrained workspace.
            '7xl' => 'w-full lg:w-11/12 max-w-7xl mx-auto',

            // Screen 2xl keeps only a small desktop gutter while retaining a cap
            // on very large displays. Default config continues to use this mode.
            default => 'w-full lg:w-11/12 2xl:w-full max-w-screen-2xl mx-auto',
        };
    }

    private function densityClass(string $density): string
    {
        return match ($density) {
            'compact' => 'px-3 py-4 sm:px-4 lg:px-5 lg:py-5',
            'dense' => 'px-3 py-3 sm:px-4 lg:px-4 lg:py-4',
            default => 'px-4 py-5 sm:px-5 lg:px-6 lg:py-6',
        };
    }
}
