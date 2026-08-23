<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminHeaderService
{
    public function __construct(
        protected AdminLayoutManager $layoutManager,
        protected AdminHeaderActionService $headerActionService,
    ) {
    }

    public function context(): array
    {
        $config = $this->layoutManager->config();
        $header = $config['header'] ?? [];
        $sidebar = $config['sidebar'] ?? [];
        $actions = $this->headerActionService->context();

        $searchEnabled = (bool) data_get($header, 'search', true);
        $userMenuEnabled = (bool) data_get($header, 'user_menu', true);
        $brandEnabled = (bool) data_get($header, 'brand.enabled', true);
        $hasActions = (bool) data_get($actions, 'notifications', true) || count((array) data_get($actions, 'items', [])) > 0;

        return [
            'sticky' => (bool) data_get($config, 'layout.sticky_header', true),
            'left' => array_values(array_filter([
                $this->component(
                    'sidebar-toggle',
                    'Admin::livewire.partials.header.components.sidebar-toggle',
                    (bool) data_get($sidebar, 'enabled', true)
                        && (bool) data_get($sidebar, 'mobile_drawer', true),
                ),
                $this->component(
                    'brand',
                    'Admin::livewire.partials.header.components.brand',
                    $brandEnabled,
                    ['brand' => $this->brandContext($header)],
                ),
                $this->component(
                    'search',
                    'Admin::livewire.partials.header.components.search',
                    $searchEnabled,
                ),
            ])),
            'right' => array_values(array_filter([
                $this->component(
                    'actions',
                    'Admin::livewire.partials.header.components.actions',
                    $hasActions,
                    ['actions' => $actions],
                ),
                $this->component(
                    'divider',
                    'Admin::livewire.partials.header.components.divider',
                    $hasActions && $userMenuEnabled,
                ),
                $this->component(
                    'user-menu',
                    'Admin::livewire.partials.header.components.user-menu',
                    $userMenuEnabled,
                ),
            ])),
        ];
    }

    protected function brandContext(array $header): array
    {
        $title = trim((string) data_get($header, 'brand.title', ''));
        $subtitle = trim((string) data_get($header, 'brand.subtitle', ''));

        return [
            'logo' => data_get($header, 'brand.logo'),
            'logo_size' => (string) data_get($header, 'brand.logo_size', '32'),
            'show_title' => (bool) data_get($header, 'brand.show_title', true),
            'title' => $title !== '' ? $title : (string) config('app.name', 'Admin'),
            'show_subtitle' => (bool) data_get($header, 'brand.show_subtitle', false),
            'subtitle' => $subtitle,
            'url' => (string) data_get($header, 'brand.url', '/admin'),
            'mobile_brand' => (string) data_get($header, 'responsive.mobile_brand', 'logo-only'),
            'hide_title_on_mobile' => (bool) data_get($header, 'responsive.hide_title_on_mobile', true),
        ];
    }

    protected function component(string $key, string $view, bool $enabled, array $data = []): ?array
    {
        if (! $enabled) {
            return null;
        }

        return [
            'key' => $key,
            'view' => $view,
            'data' => $data,
        ];
    }
}
