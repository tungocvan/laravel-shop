<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemDashboardInformationArchitectureContractTest extends TestCase
{
    public function test_dashboard_uses_grouped_system_workspaces_without_legacy_general_cards(): void
    {
        $service = file_get_contents(base_path('Modules/System/Services/SystemDashboardService.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/dashboard.blade.php'));

        $this->assertIsString($service);
        $this->assertIsString($view);

        foreach (['Vận hành', 'Dữ liệu & Khôi phục', 'Cấu hình hạ tầng', 'Quản trị truy cập', 'Công cụ kỹ thuật'] as $group) {
            $this->assertStringContainsString($group, $service.$view);
        }

        foreach (['Quản lý Modules', 'Queue Manager', 'Database Manager', 'Backup / Restore', 'Môi trường & Tích hợp', 'Giao diện & Đăng nhập'] as $workspace) {
            $this->assertStringContainsString($workspace, $service.$view);
        }

        $this->assertStringNotContainsString("'label' => 'System workspace'", $service);
        $this->assertStringNotContainsString("'label' => 'Cấu hình chung'", $service);
        $this->assertStringNotContainsString("'code' => 'settings'", $service);
        $this->assertStringContainsString("route('admin.system.index', ['tab' => 'queues'])", $view);
    }

    public function test_system_operations_no_longer_owns_sidebar_theme(): void
    {
        $tabs = file_get_contents(base_path('Modules/System/config/system_tabs.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/system.blade.php'));
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/SystemController.php'));

        $this->assertStringNotContainsString("'id' => 'themes'", $tabs);
        $this->assertStringNotContainsString('admin.theme-switcher', $tabs);
        $this->assertStringContainsString("'id' => 'queues'", $tabs);
        $this->assertStringContainsString('System Operations', $view);
        $this->assertStringContainsString("request->query('tab'", $controller);
        $this->assertStringContainsString('$activeTab', $controller);
    }

    public function test_environment_labels_reflect_runtime_and_web_integration_ownership(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/EnvConfigController.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/settings/env.blade.php'));

        $this->assertStringContainsString('Runtime & Bridge', $controller);
        $this->assertStringContainsString('Web & Analytics', $controller);
        $this->assertStringNotContainsString("'label' => 'SEO & Social'", $controller);
        $this->assertStringContainsString('Môi trường & Tích hợp', $view);
    }

    public function test_login_navigation_is_consolidated_into_login_experience(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/SettingController.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/pages/settings/login-theme.blade.php'));
        $index = file_get_contents(base_path('Modules/System/resources/views/pages/settings/index.blade.php'));

        $this->assertStringContainsString('Giao diện & Đăng nhập', $view);
        $this->assertStringContainsString('Đăng nhập & Điều hướng', $view);
        $this->assertStringContainsString("route('admin.system.settings.login-redirect.update')", $view);
        $this->assertStringContainsString("route('admin.system.settings.login-theme')", $controller);
        $this->assertStringNotContainsString("@livewire('system.settings.setting-form')", $index);
        $this->assertStringContainsString('Themes, hình ảnh và cấu hình chung không còn là tab riêng', $index);
    }
}
