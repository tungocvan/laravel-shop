<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminSidebarSettingsContractTest extends TestCase
{
    public function test_sidebar_defaults_expose_managed_regions_search_background_width_and_controls(): void
    {
        $config = require base_path('Modules/Admin/config/admin.php');

        $this->assertTrue($config['sidebar']['header']['enabled']);
        $this->assertNull($config['sidebar']['header']['title']);
        $this->assertTrue($config['sidebar']['footer']['enabled']);
        $this->assertTrue($config['sidebar']['search']['enabled']);
        $this->assertSame('theme', $config['sidebar']['presentation']['background']);
        $this->assertSame('256px', $config['sidebar']['expanded_width']);
        $this->assertSame('80px', $config['sidebar']['collapsed_width']);
        $this->assertTrue($config['sidebar']['controls']['collapse_enabled']);
        $this->assertTrue($config['sidebar']['controls']['fullscreen_enabled']);
        $this->assertSame('Không gian quản trị', $config['sidebar']['header']['subtitle']);
        $this->assertSame('Tài khoản quản trị', $config['sidebar']['footer']['subtitle']);
    }

    public function test_sidebar_manager_normalizes_new_settings_with_bounded_values(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString("'sidebar' => \$this->sidebarDefaults()", $manager);
        $this->assertStringContainsString("'sidebar' => \$this->normalizeSidebar", $manager);
        $this->assertStringContainsString('private function sidebarDefaults(): array', $manager);
        $this->assertStringContainsString('private function normalizeSidebar(array $sidebar, array $defaults): array', $manager);
        $this->assertStringContainsString('private function sidebarWidth(', $manager);
        $this->assertStringContainsString("'collapse_enabled' =>", $manager);
        $this->assertStringContainsString("'fullscreen_enabled' =>", $manager);
        $this->assertStringContainsString("'title' => \$this->nullableString(data_get(\$sidebar, 'header.title'))", $manager);
        $this->assertStringContainsString("\$legacy = ['system' => 'light', 'white' => 'light']", $manager);
        $this->assertStringContainsString("['theme', 'light', 'dark', 'custom']", $manager);
    }

    public function test_sidebar_runtime_consumes_managed_regions_title_controls_and_search_policy(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Partials/Sidebar.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/sidebar.blade.php'));

        foreach ([
            'sidebar.header.enabled',
            'sidebar.header.show_mark',
            'sidebar.header.show_title',
            'sidebar.header.title',
            'sidebar.footer.enabled',
            'sidebar.search.enabled',
            'sidebar.controls.collapse_enabled',
            'sidebar.controls.fullscreen_enabled',
        ] as $contract) {
            $this->assertStringContainsString($contract, $component);
        }

        $this->assertStringContainsString('$searchEnabled && $this->destinationCount >= $searchThreshold', $component);
        $this->assertStringContainsString('@if ($showSidebarHeader)', $view);
        $this->assertStringContainsString('@if ($showSidebarFooter)', $view);
        $this->assertStringContainsString('@if ($showNavigationSearch)', $view);
        $this->assertStringContainsString('$headerTitle !==', $view);
        $this->assertStringContainsString('{{ $headerSubtitle }}', $view);
        $this->assertStringContainsString('{{ $footerSubtitle }}', $view);
        $this->assertStringContainsString('data-admin-sidebar-collapse-toggle', $view);
        $this->assertStringContainsString('data-admin-sidebar-fullscreen-enter', $view);
        $this->assertStringContainsString('{{ $sidebarSurfaceClass }}', $view);
    }

    public function test_disabled_sidebar_keeps_a_desktop_reveal_control_and_canonical_grid_runtime(): void
    {
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));

        $this->assertStringContainsString('data-admin-sidebar-disabled-reveal', $shell);
        $this->assertStringContainsString('aria-label="Mở Sidebar"', $shell);
        $this->assertStringContainsString('sidebarFullscreen = false; sidebarOpen = true', $shell);
        $this->assertStringContainsString("if (!{{ \$sidebarEnabled ? 'true' : 'false' }}) { sidebarOpen = false; sidebarFullscreen = false; }", $shell);
        $this->assertStringContainsString(": (sidebarOpen && !sidebarFullscreen)", $shell);
        $this->assertStringContainsString("? ' lg:translate-x-0' : ''", $shell);

        $this->assertStringContainsString('transition-[grid-template-columns]', $shell);
        $this->assertStringContainsString("gridTemplateColumns: sidebarOpen ? '{{ \$adminShellPresentation['sidebar_expanded_width'] }} minmax(0, 1fr)' : '{{ \$adminShellPresentation['sidebar_collapsed_width'] }} minmax(0, 1fr)'", $shell);
        $this->assertStringContainsString("gridTemplateColumns: '0 minmax(0, 1fr)'", $shell);
        $this->assertStringContainsString('lg:relative lg:inset-auto lg:col-start-1 lg:row-start-1', $shell);
        $this->assertStringContainsString('data-admin-shell-grid', $shell);
        $this->assertStringContainsString('data-admin-shell-sidebar', $shell);
        $this->assertStringContainsString('data-admin-shell-workspace', $shell);
        $this->assertStringContainsString('[data-admin-shell-grid] [data-admin-shell-workspace]', $shell);
        $this->assertStringContainsString('grid-column: 2;', $shell);
        $this->assertStringContainsString('@media (min-width: 1024px)', $shell);
        $this->assertStringContainsString('[data-admin-shell-grid] [data-admin-shell-sidebar]', $shell);
        $this->assertStringContainsString('position: relative;', $shell);
        $this->assertStringContainsString('class="grid min-h-0 min-w-0 overflow-hidden"', $shell);
        $this->assertStringNotContainsString("transition-[left]", $shell);
        $this->assertStringNotContainsString("? 'left: {{ \$adminShellPresentation['sidebar_expanded_width'] }}'", $shell);

        $header = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));
        $this->assertStringNotContainsString('headerSidebarEnabled', $header);
        $this->assertStringNotContainsString('headerSidebarExpandedWidth', $header);
        $this->assertStringNotContainsString('headerSidebarCollapsedWidth', $header);
        $this->assertStringNotContainsString('paddingLeft: sidebarOpen', $header);
        $this->assertStringContainsString('style="padding-inline: {{ $adminShellPresentation[\'header_padding_x\'] }};"', $header);
    }

    public function test_sidebar_settings_use_dedicated_professional_editor_and_live_preview(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));

        $this->assertStringContainsString("if (\$this->section === 'sidebar')", $component);
        $this->assertStringContainsString("data_set(\$this->config, 'sidebar.presentation.background'", $component);
        $this->assertStringContainsString("private function sidebarBackgroundMode(mixed \$value): string", $component);
        $this->assertStringContainsString("'system', 'white' => 'light'", $component);
        $this->assertStringContainsString('admin-sidebar-config', $component);
        $this->assertStringContainsString("'config.sidebar.expanded_width' => \$sidebarWidth", $component);
        $this->assertStringContainsString("'config.sidebar.collapsed_width' => \$sidebarWidth", $component);
        $this->assertStringContainsString("'config.sidebar.controls.collapse_enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.controls.fullscreen_enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.header.title' => 'nullable|string|max:80'", $component);
        $this->assertStringContainsString("'config.sidebar.header.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.footer.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.search.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.presentation.background' => 'required|in:theme,light,dark,custom'", $component);
        $this->assertStringContainsString("'config.sidebar.presentation.custom_background' => ['exclude_unless:config.sidebar.presentation.background,custom', 'required'", $component);
        $this->assertStringContainsString("'config.sidebar.presentation.custom_accent' => ['exclude_unless:config.sidebar.presentation.background,custom', 'required'", $component);
        $this->assertStringContainsString("'config.sidebar.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.enabled'=>['Bật Sidebar'", $view);
        $this->assertStringContainsString('wire:model.live="{{ $model }}"', $view);

        foreach (['Giao diện Sidebar', 'Kích thước & hành vi', 'Nút thu gọn / mở rộng', 'Header Sidebar', 'Tìm chức năng', 'Footer Sidebar', 'Sidebar background', 'Sidebar preview', 'Lưu Sidebar'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('config.sidebar.header.title', $view);
        $this->assertStringContainsString('config.sidebar.expanded_width', $view);
        $this->assertStringContainsString('config.sidebar.collapsed_width', $view);
        $this->assertStringContainsString('wire:model.live="config.sidebar.controls.collapse_enabled"', $view);
        $this->assertStringContainsString('wire:model.live="config.sidebar.controls.fullscreen_enabled"', $view);
        $this->assertStringContainsString('wire:model.live="config.sidebar.header.enabled"', $view);
        $this->assertStringContainsString('wire:model.live="config.sidebar.footer.enabled"', $view);
        $this->assertStringContainsString('wire:model.live="config.sidebar.search.enabled"', $view);
        $this->assertStringContainsString("\$wire.set('config.sidebar.presentation.background', mode)", $view);
        $this->assertStringNotContainsString('wire:model="config.sidebar.presentation.background"', $view);
        $this->assertStringContainsString('Không thể lưu Sidebar', $view);
        $this->assertStringContainsString('$errors->all()', $view);
        $this->assertStringContainsString('role="dialog"', $view);
    }
}
