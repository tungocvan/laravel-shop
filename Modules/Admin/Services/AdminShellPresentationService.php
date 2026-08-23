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
            'full' => 'max-w-none',
            'narrow' => 'max-w-5xl mx-auto',
            '7xl' => 'max-w-7xl mx-auto',
            default => 'max-w-screen-2xl mx-auto',
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
