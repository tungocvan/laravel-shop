<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminSidebarContractTest extends TestCase
{
    public function test_sidebar_service_builds_render_ready_navigation_view_models(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/SidebarService.php'));

        $this->assertStringContainsString("'kind' => 'item'", $service);
        $this->assertStringContainsString("'kind' => 'group'", $service);
        $this->assertStringContainsString("'href' => \$this->href(", $service);
        $this->assertStringContainsString("'group_id' => 'admin-nav-group-'", $service);
        $this->assertStringContainsString("'active' => (bool)", $service);
        $this->assertStringContainsString("'children' => \$children", $service);
    }

    public function test_sidebar_service_keeps_permission_pruning_and_active_state_out_of_blade(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/SidebarService.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringContainsString('protected function canAccess(', $service);
        $this->assertStringContainsString('protected function withActiveState(', $service);
        $this->assertStringContainsString("\$user->can(\$item['can'])", $service);
        $this->assertStringNotContainsString("collect(\$menu['children']", $view);
        $this->assertStringNotContainsString('$hasChildren', $view);
        $this->assertStringNotContainsString('$isActive', $view);
        $this->assertStringNotContainsString('$groupId', $view);
        $this->assertStringNotContainsString('->can(', $view);
    }

    public function test_sidebar_view_renders_item_and_group_registry_partials(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));

        $this->assertStringContainsString("sidebar.navigation.' . \$item['kind']", $view);
        $this->assertStringContainsString("href=\"{{ \$item['href'] }}\"", $item);
        $this->assertStringContainsString('aria-current="page"', $item);
        $this->assertStringContainsString("\$item['group_id']", $group);
        $this->assertStringContainsString("\$item['children']", $group);
        $this->assertStringContainsString("href=\"{{ \$child['href'] }}\"", $group);
    }

    public function test_livewire_sidebar_does_not_own_browser_open_state_anymore(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Partials/Sidebar.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringNotContainsString('public $sidebarOpen', $component);
        $this->assertStringNotContainsString('public bool $sidebarOpen', $component);
        $this->assertStringNotContainsString('function toggleSidebar(', $component);
        $this->assertStringNotContainsString("session(['sidebar_open'", $component);
        $this->assertStringContainsString('@click="toggleSidebar($event.currentTarget)"', $view);
        $this->assertStringContainsString('public bool $desktopCollapsible = true;', $component);
    }

    public function test_sidebar_blade_does_not_query_layout_config_or_auth_directly(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Partials/Sidebar.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringContainsString('AdminLayoutManager $layoutManager', $component);
        $this->assertStringContainsString('public bool $showSidebarFooter = true;', $component);
        $this->assertStringContainsString('public string $profileName', $component);
        $this->assertStringContainsString("sidebar.footer.enabled", $component);
        $this->assertStringNotContainsString('AdminLayoutManager::class', $view);
        $this->assertStringNotContainsString('auth()->user()', $view);
        $this->assertStringContainsString('@if ($showSidebarFooter)', $view);
        $this->assertStringContainsString('{{ $profileName }}', $view);
    }
}
