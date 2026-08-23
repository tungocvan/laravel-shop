<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminSidebarFullscreenToggleContractTest extends TestCase
{
    public function test_layout_owns_a_distinct_fullscreen_sidebar_state(): void
    {
        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));

        $this->assertStringContainsString('sidebarFullscreen: false', $head);
        $this->assertStringContainsString('readSidebarFullscreenPreference()', $head);
        $this->assertStringContainsString('persistSidebarFullscreenPreference()', $head);
        $this->assertStringContainsString('toggleSidebarFullscreen(trigger)', $head);
        $this->assertStringContainsString("admin.sidebar.fullscreen", $head);
        $this->assertStringContainsString('this.sidebarFullscreen = !this.sidebarFullscreen', $head);
        $this->assertStringNotContainsString('this.sidebarOpen = !this.sidebarFullscreen', $head);
    }

    public function test_fullscreen_mode_hides_sidebar_and_removes_shell_margin(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));

        $this->assertStringContainsString('x-show="!isDesktop || !sidebarFullscreen"', $shell);
        $this->assertStringContainsString('isDesktop && !sidebarFullscreen', $shell);
        $this->assertStringContainsString("'margin-left: 0'", $shell);
        $this->assertStringContainsString('data-admin-sidebar-fullscreen', $shell);
        $this->assertStringContainsString('data-admin-sidebar-fullscreen-toggle', $shell);
        $this->assertStringContainsString('x-show="isDesktop && sidebarFullscreen"', $shell);
        $this->assertStringContainsString('Mở lại Sidebar', $shell);
    }

    public function test_shell_owns_fullscreen_entry_control_without_coupling_it_to_sidebar_content(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $sidebar = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        $this->assertStringContainsString('data-admin-sidebar-fullscreen-toggle', $shell);
        $this->assertStringContainsString('@click="toggleSidebarFullscreen($event.currentTarget)"', $shell);
        $this->assertStringContainsString('Ẩn Sidebar toàn màn hình', $shell);
        $this->assertStringNotContainsString('data-admin-sidebar-fullscreen-enter', $sidebar);
    }

    public function test_header_reserves_only_toggle_space_while_shell_remains_full_width(): void
    {
        $header = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));

        $this->assertStringContainsString("sidebarFullscreen ? { paddingLeft: '4rem' } : {}", $header);
        $this->assertStringContainsString("@include('Admin::layouts.partials.content')", $shell);
        $this->assertStringContainsString("@include('Admin::layouts.partials.footer')", $shell);
        $this->assertStringContainsString("'margin-left: 0'", $shell);
    }
}
