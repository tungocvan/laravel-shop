<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminContentWorkspaceContractTest extends TestCase
{
    public function test_page_header_owns_professional_page_hierarchy(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/components/page-header.blade.php'));

        $this->assertStringContainsString("'title'", $view);
        $this->assertStringContainsString("'description' => null", $view);
        $this->assertStringContainsString('text-2xl font-semibold', $view);
        $this->assertStringContainsString('@isset($actions)', $view);
        $this->assertStringContainsString('@isset($toolbar)', $view);
        $this->assertStringContainsString('border-t border-slate-200/80', $view);
    }

    public function test_content_section_keeps_sections_semantic_without_forcing_cards(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/components/content-section.blade.php'));

        $this->assertStringContainsString('<section', $view);
        $this->assertStringContainsString('text-lg font-semibold', $view);
        $this->assertStringContainsString('{{ $slot }}', $view);
        $this->assertStringNotContainsString('shadow-', $view);
        $this->assertStringNotContainsString('rounded-', $view);
        $this->assertStringNotContainsString('bg-white', $view);
    }

    public function test_layout_hub_uses_shared_page_header_without_duplicate_livewire_heading(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout.blade.php'));
        $dashboard = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-dashboard.blade.php'));

        $this->assertStringContainsString('<x-admin::page-header', $page);
        $this->assertStringContainsString('<x-admin::content-section>', $page);
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-dashboard')", $page);
        $this->assertStringNotContainsString('<h1', $dashboard);
        $this->assertStringContainsString('grid grid-cols-1 gap-5', $dashboard);
    }

    public function test_layout_sections_use_shared_page_header_without_duplicate_livewire_heading(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));
        $config = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));

        $this->assertStringContainsString('<x-admin::page-header', $page);
        $this->assertStringContainsString(':title="$title"', $page);
        $this->assertStringContainsString('<x-slot:actions>', $page);
        $this->assertStringContainsString("route('admin.layout')", $page);
        $this->assertStringContainsString('<x-admin::content-section>', $page);
        $this->assertStringNotContainsString('<h1', $config);
        $this->assertStringContainsString('wire:click="resetSection"', $config);
        $this->assertStringContainsString('wire:submit="save"', $config);
    }

    public function test_header_manager_uses_page_header_toolbar_and_links_to_canonical_design_page(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/header/index.blade.php'));

        $this->assertStringContainsString('<x-admin::page-header', $view);
        $this->assertStringContainsString('<x-slot:toolbar>', $view);
        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString('role="tab"', $view);
        $this->assertStringContainsString(':aria-selected=', $view);
        $this->assertStringContainsString('<x-admin::content-section>', $view);
        $this->assertStringNotContainsString('max-w-7xl mx-auto py-6', $view);
        $this->assertStringNotContainsString('Homepage Header Manager', $view);
        $this->assertStringContainsString("@livewire('admin.header.general-settings')", $view);
        $this->assertStringContainsString("@livewire('admin.header.menu-manager')", $view);
        $this->assertStringContainsString("route('admin.layout.design')", $view);
        $this->assertStringNotContainsString("@livewire('admin.theme-switcher')", $view);
    }

    public function test_legacy_themes_url_redirects_to_canonical_layout_design_route(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringContainsString("Route::get('/themes', fn () => redirect()->route('admin.layout.design'))", $routes);
        $this->assertStringContainsString("->name('themes');", $routes);
        $this->assertStringContainsString("Route::get('/design', [AdminController::class, 'layoutDesign'])->name('design');", $routes);
    }
}
