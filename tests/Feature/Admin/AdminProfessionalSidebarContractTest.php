<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminProfessionalSidebarContractTest extends TestCase
{
    public function test_sidebar_adapts_presentation_for_sparse_and_large_navigation(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Partials/Sidebar.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));
        $settings = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));

        $this->assertStringContainsString('public int $destinationCount = 0;', $component);
        $this->assertStringContainsString('sidebar.navigation_search_threshold', $component);
        $this->assertStringContainsString('$this->destinationCount >= $searchThreshold', $component);
        $this->assertStringContainsString('navigation_search_threshold', $manager);
        $this->assertStringContainsString('config.sidebar.navigation_search_threshold', $settings);
        $this->assertStringContainsString('@if ($showNavigationSearch)', $view);
        $this->assertStringContainsString('Tìm chức năng...', $view);
        $this->assertStringContainsString('x-model.debounce.120ms="navQuery"', $view);
        $this->assertStringContainsString("collect(\$item['children'] ?? [])->pluck('name')", $view);
        $this->assertStringContainsString('x-show="matches(@js(', $view);
        $this->assertStringContainsString('filterEnabled: @js($showNavigationSearch)', $group);
        $this->assertStringContainsString("matches(@js(\$child['name']))", $group);
        $this->assertStringContainsString('[scrollbar-gutter:stable]', $view);
    }

    public function test_sidebar_has_clear_workspace_and_profile_hierarchy(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));
        $this->assertStringContainsString('Không gian quản trị', $view);
        $this->assertStringContainsString('Điều hướng', $view);
        $this->assertStringContainsString('Tài khoản quản trị', $view);
        $this->assertStringContainsString('{{ $profileName }}', $view);
    }

    public function test_navigation_items_have_professional_active_hierarchy_without_changing_route_contract(): void
    {
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));
        $this->assertStringContainsString("href=\"{{ \$item['href'] }}\"", $item);
        $this->assertStringContainsString('aria-current="page"', $item);
        $this->assertStringContainsString('bg-indigo-50 text-indigo-700', $item);
        $this->assertStringContainsString("\$item['group_id']", $group);
        $this->assertStringContainsString("href=\"{{ \$child['href'] }}\"", $group);
        $this->assertStringContainsString('border-l border-slate-200', $group);
    }

    public function test_sidebar_redesign_does_not_move_permission_logic_into_views(): void
    {
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));
        $item = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/item.blade.php'));
        $group = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar/navigation/group.blade.php'));
        foreach ([$sidebar, $item, $group] as $view) {
            $this->assertStringNotContainsString('auth()->user()', $view);
            $this->assertStringNotContainsString('->can(', $view);
        }
    }
}
