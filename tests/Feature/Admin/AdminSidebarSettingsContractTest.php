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
        $this->assertStringContainsString("['theme', 'system', 'white', 'dark']", $manager);
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
            'sidebar.presentation.background',
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

    public function test_sidebar_settings_use_dedicated_professional_editor_and_live_preview(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-sidebar-config.blade.php'));

        $this->assertStringContainsString("if (\$this->section === 'sidebar')", $component);
        $this->assertStringContainsString('admin-sidebar-config', $component);
        $this->assertStringContainsString("'config.sidebar.expanded_width' => \$sidebarWidth", $component);
        $this->assertStringContainsString("'config.sidebar.collapsed_width' => \$sidebarWidth", $component);
        $this->assertStringContainsString("'config.sidebar.controls.collapse_enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.controls.fullscreen_enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.header.title' => 'nullable|string|max:80'", $component);
        $this->assertStringContainsString("'config.sidebar.header.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.footer.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.search.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.sidebar.presentation.background' => 'required|in:theme,system,white,dark'", $component);

        foreach (['Kích thước Sidebar', 'Nút điều khiển Sidebar', 'Header Sidebar', 'Tìm chức năng Sidebar', 'Footer Sidebar', 'Sidebar background', 'Sidebar preview', 'Lưu Sidebar'] as $label) {
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
        $this->assertStringContainsString('wire:model.live="config.sidebar.presentation.background"', $view);
    }
}
