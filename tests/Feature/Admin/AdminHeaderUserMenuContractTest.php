<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminHeaderUserMenuContractTest extends TestCase
{
    public function test_header_user_consumes_dedicated_runtime_service(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Partials/HeaderUser.php'));

        $this->assertStringContainsString('AdminHeaderUserMenuService', $component);
        $this->assertStringContainsString('public array $userMenuContext = []', $component);
        $this->assertStringContainsString('$userMenuService->context($this->user)', $component);
        $this->assertStringNotContainsString('HeaderMenuService $headerMenuService', $component);
    }

    public function test_user_menu_service_enforces_enabled_permission_order_safe_url_and_icon_registry(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderUserMenuService.php'));

        $this->assertStringContainsString("private const ICONS = [", $service);
        $this->assertStringContainsString("data_get(\$item, 'enabled', true)", $service);
        $this->assertStringContainsString("data_get(\$item, 'permission', '')", $service);
        $this->assertStringContainsString('Gate::forUser($user)->allows($permission)', $service);
        $this->assertStringContainsString("data_get(\$item, 'order', 0)", $service);
        $this->assertStringContainsString('private function safeUrl(', $service);
        $this->assertStringContainsString('private function safeIcon(', $service);
        $this->assertStringContainsString("str_starts_with(\$url, '/')", $service);
        $this->assertStringContainsString("str_starts_with(\$url, '//')", $service);
    }

    public function test_user_menu_view_honors_visibility_flags_and_keeps_logout_system_owned(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header-user.blade.php'));

        foreach (['show_avatar', 'show_name', 'show_email', 'show_role'] as $key) {
            $this->assertStringContainsString($key, $view);
        }

        $this->assertStringContainsString("\$menuItems = (array) data_get(\$userMenuContext, 'items', [])", $view);
        $this->assertStringContainsString("route('admin.logout')", $view);
        $this->assertStringContainsString('Đăng xuất', $view);
        $this->assertStringContainsString('rel="noopener noreferrer"', $view);
    }

    public function test_legacy_database_menu_remains_fallback_until_configured_items_exist(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminHeaderUserMenuService.php'));

        $this->assertStringContainsString("getMenuTreeByLocation('admin')", $service);
        $this->assertStringContainsString("'label' => 'Profile'", $service);
        $this->assertStringContainsString("route('admin.profile')", $service);
    }
}
