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

        $this->assertStringContainsString('data-admin-shell', $shell);
        $this->assertStringContainsString('--admin-sidebar-width: 0px;', $shell);
        $this->assertStringContainsString("const sidebarWidth = isDesktop", $shell);
        $this->assertStringContainsString("? (sidebarOpen ? '{{ \$adminShellPresentation['sidebar_expanded_width'] }}' : '{{ \$adminShellPresentation['sidebar_collapsed_width'] }}')", $shell);
        $this->assertStringContainsString("\$el.style.setProperty('--admin-sidebar-width', sidebarWidth);", $shell);
        $this->assertStringNotContainsString("':style=\"{", $shell);
        $this->assertStringContainsString(": '0px'", $shell);
        $this->assertStringContainsString('[data-admin-shell] [data-admin-shell-sidebar]', $shell);
        $this->assertStringContainsString('position: fixed;', $shell);
        $this->assertStringContainsString('[data-admin-shell] [data-admin-shell-workspace]', $shell);
        $this->assertStringContainsString('margin-left: var(--admin-sidebar-width);', $shell);
        $this->assertStringContainsString('width: calc(100% - var(--admin-sidebar-width));', $shell);
        $this->assertStringContainsString('transition-[margin-left,width]', $shell);
        $this->assertStringContainsString("isDesktop\n                ? 'translate-x-0'\n                : (sidebarOpen ? 'translate-x-0' : '-translate-x-full')", $shell);
        $this->assertStringNotContainsString("' lg:translate-x-0'", $shell);
        $this->assertStringContainsString("? (sidebarOpen ? '{{ \$adminShellPresentation['sidebar_expanded_width'] }}' : '{{ \$adminShellPresentation['sidebar_collapsed_width'] }}')", $shell);

        $this->assertStringNotContainsString('data-admin-shell-grid', $shell);
        $this->assertStringNotContainsString('grid-template-columns: var(--admin-sidebar-track-width)', $shell);
        $this->assertStringNotContainsString('gridTemplateColumns:', $shell);

        $head = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $this->assertStringContainsString('desktopBreakpoint: 1024', $head);
        $this->assertStringContainsString('isDesktopViewport()', $head);
        $this->assertStringContainsString('document.documentElement.clientWidth || window.innerWidth || 0', $head);
        $this->assertStringContainsString('viewportWidth >= this.desktopBreakpoint', $head);
        $this->assertStringContainsString('const wasDesktop = this.isDesktop;', $head);
        $this->assertStringContainsString('const nextIsDesktop = this.isDesktopViewport();', $head);
        $this->assertStringContainsString('if (!wasDesktop)', $head);
        $this->assertStringNotContainsString("window.matchMedia('(min-width: 1024px)').matches", $head);

        $header = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header.blade.php'));
        $this->assertStringNotContainsString('headerSidebarEnabled', $header);
        $this->assertStringNotContainsString('headerSidebarExpandedWidth', $header);
        $this->assertStringNotContainsString('headerSidebarCollapsedWidth', $header);
        $this->assertStringNotContainsString('paddingLeft: sidebarOpen', $header);
        $this->assertStringContainsString('data-admin-header-grid', $header);
        $this->assertStringContainsString('grid-template-columns: minmax(0, 1fr) auto;', $header);
        $this->assertStringContainsString('data-admin-header-left', $header);
        $this->assertStringContainsString('grid-template-columns: repeat(2, max-content) minmax(0, 1fr);', $header);
        $this->assertStringContainsString('data-admin-header-right', $header);
        $this->assertStringContainsString("padding-inline: {{ \$adminShellPresentation['header_padding_x'] }};", $header);

        $search = file_get_contents(base_path('Modules/Admin/resources/views/livewire/partials/header/components/search.blade.php'));
        $this->assertStringContainsString('data-admin-header-search-column', $search);
        $this->assertStringContainsString('class="hidden min-w-0 w-full lg:block"', $search);
        $this->assertStringNotContainsString('hidden min-w-0 flex-1 lg:block', $search);
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
