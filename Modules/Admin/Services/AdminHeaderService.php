<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminHeaderService
{
    public function __construct(
        protected AdminLayoutManager $layoutManager,
    ) {
    }

    public function context(): array
    {
        $config = $this->layoutManager->config();
        $header = $config['header'] ?? [];
        $sidebar = $config['sidebar'] ?? [];

        $searchEnabled = (bool) data_get($header, 'search', true);
        $notificationsEnabled = (bool) data_get($header, 'notifications', true);
        $userMenuEnabled = (bool) data_get($header, 'user_menu', true);

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
                    'search',
                    'Admin::livewire.partials.header.components.search',
                    $searchEnabled,
                ),
            ])),
            'right' => array_values(array_filter([
                $this->component(
                    'notifications',
                    'Admin::livewire.partials.header.components.notifications',
                    $notificationsEnabled,
                ),
                $this->component(
                    'divider',
                    'Admin::livewire.partials.header.components.divider',
                    $notificationsEnabled && $userMenuEnabled,
                ),
                $this->component(
                    'user-menu',
                    'Admin::livewire.partials.header.components.user-menu',
                    $userMenuEnabled,
                ),
            ])),
        ];
    }

    protected function component(string $key, string $view, bool $enabled): ?array
    {
        if (! $enabled) {
            return null;
        }

        return [
            'key' => $key,
            'view' => $view,
        ];
    }
}
