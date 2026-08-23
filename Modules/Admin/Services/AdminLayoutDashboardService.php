<?php

namespace Modules\Admin\Services;

use Modules\Admin\Support\AdminLayoutManager;

class AdminLayoutDashboardService
{
    public function __construct(
        private readonly AdminLayoutManager $layoutManager,
    ) {
    }

    public function cards(): array
    {
        $config = $this->layoutManager->config();

        return [
            [
                'key' => 'general',
                'title' => 'Layout tổng thể',
                'description' => 'Preset, container, density, locale và sticky header.',
                'route' => 'admin.layout.general',
                'status' => sprintf('%s · %s', data_get($config, 'layout.preset', 'default'), data_get($config, 'layout.density', 'comfortable')),
            ],
            [
                'key' => 'header',
                'title' => 'Header',
                'description' => 'Search, notification, user menu và hành vi sticky.',
                'route' => 'admin.layout.header',
                'status' => data_get($config, 'header.search', true) ? 'Search đang bật' : 'Search đang tắt',
            ],
            [
                'key' => 'sidebar',
                'title' => 'Sidebar',
                'description' => 'Hiển thị, drawer mobile, collapse desktop và persistence.',
                'route' => 'admin.layout.sidebar',
                'status' => data_get($config, 'sidebar.enabled', true) ? 'Đang bật' : 'Đang tắt',
            ],
            [
                'key' => 'footer',
                'title' => 'Footer',
                'description' => 'Visibility và các thành phần hiển thị ở Footer.',
                'route' => 'admin.layout.footer',
                'status' => data_get($config, 'layout.show_footer', false) ? 'Đang bật' : 'Đang tắt',
            ],
            [
                'key' => 'design',
                'title' => 'Giao diện & Theme',
                'description' => 'Sidebar theme, accent và theme runtime hiện tại.',
                'route' => 'admin.layout.design',
                'status' => (string) data_get($config, 'theme.default', 'corporate-blue'),
            ],
            [
                'key' => 'navigation',
                'title' => 'Navigation',
                'description' => 'Cache TTL, active strategy và độ sâu menu.',
                'route' => 'admin.layout.navigation',
                'status' => 'Depth '.(int) data_get($config, 'navigation.max_depth', 2),
            ],
        ];
    }
}
