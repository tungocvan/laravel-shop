<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemDashboardNavigationContractTest extends TestCase
{
    public function test_system_workspaces_reuse_dashboard_return_link(): void
    {
        foreach ([
            'Modules/System/resources/views/system.blade.php',
            'Modules/System/resources/views/pages/settings/index.blade.php',
            'Modules/System/resources/views/pages/settings/modules.blade.php',
            'Modules/System/resources/views/pages/settings/artisan.blade.php',
            'Modules/System/resources/views/pages/settings/scripts.blade.php',
            'Modules/System/resources/views/pages/settings/login-theme.blade.php',
            'Modules/System/resources/views/pages/database.blade.php',
        ] as $path) {
            $view = file_get_contents(base_path($path));
            $this->assertStringContainsString("System::partials.dashboard-return-link", $view, $path);
        }
    }

    public function test_admin_layout_pages_link_back_without_a_hard_system_view_dependency(): void
    {
        foreach ([
            'Modules/Admin/resources/views/pages/admin/layout.blade.php',
            'Modules/Admin/resources/views/pages/admin/layout-section.blade.php',
        ] as $path) {
            $view = file_get_contents(base_path($path));
            $this->assertStringContainsString("Route::has('admin.system.dashboard')", $view);
            $this->assertStringContainsString("can('system.manage')", $view);
            $this->assertStringContainsString("route('admin.system.dashboard')", $view);
            $this->assertStringNotContainsString("System::partials.dashboard-return-link", $view);
        }
    }

    public function test_dashboard_preserves_read_only_status_and_provides_hub_entry(): void
    {
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/dashboard.blade.php'));
        $service = file_get_contents(base_path('Modules/System/Services/SystemDashboardService.php'));

        $this->assertStringContainsString("route('admin.system.settings.index')", $view);
        $this->assertStringContainsString("['settings_view']", $view);
        $this->assertStringContainsString('$dashboard->warnings', $view);
        $this->assertStringContainsString('$dashboard->subsystems', $view);
        $this->assertStringContainsString('Realtime thuộc NodeJS Bridge', $service);
    }
}
