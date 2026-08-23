<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminResponsivePresentationContractTest extends TestCase
{
    public function test_desktop_breakpoint_is_shared_by_runtime_drawer_and_search_contract(): void
    {
        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/search.blade.php'));
        $stacks = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/stacks.blade.php'));

        $this->assertStringContainsString("matchMedia('(min-width: 1024px)')", $head);
        $this->assertStringContainsString('lg:hidden', $shell);
        $this->assertStringContainsString('lg:hidden', $search);
        $this->assertStringContainsString('lg:block', $search);
        $this->assertStringContainsString('x-show="searchOpen && !isDesktop"', $stacks);
        $this->assertStringContainsString('lg:hidden', $stacks);
        $this->assertStringNotContainsString('sm:hidden', $search);
        $this->assertStringNotContainsString('sm:block', $search);
        $this->assertStringNotContainsString('sm:hidden', $stacks);
    }

    public function test_shell_is_the_single_owner_of_sidebar_width(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringContainsString('sidebar_expanded_width', $shell);
        $this->assertStringContainsString('sidebar_collapsed_width', $shell);
        $this->assertStringContainsString('w-full', $sidebar);
        $this->assertStringNotContainsString("sidebarOpen ? 'w-64' : 'w-20'", $sidebar);
    }

    public function test_mobile_drawer_does_not_expose_desktop_collapse_control(): void
    {
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringContainsString('lg:inline-flex', $sidebar);
        $this->assertStringContainsString('aria-controls="admin-sidebar"', $sidebar);
        $this->assertStringContainsString(':aria-expanded="sidebarOpen.toString()"', $sidebar);
    }

    public function test_mobile_search_controls_meet_touch_target_contract(): void
    {
        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/search.blade.php'));
        $stacks = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/stacks.blade.php'));

        $this->assertStringContainsString('h-11 w-11', $search);
        $this->assertStringContainsString('h-11 w-11', $stacks);
    }

    public function test_header_uses_shell_height_and_semantic_surface_contract(): void
    {
        $header = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));

        $this->assertStringContainsString('AdminShellPresentationService::class', $header);
        $this->assertStringContainsString('header_height', $header);
        $this->assertStringContainsString('var(--admin-surface-raised)', $header);
        $this->assertStringContainsString('var(--admin-border-subtle)', $header);
        $this->assertStringNotContainsString('h-16 items-center', $header);
    }
}
