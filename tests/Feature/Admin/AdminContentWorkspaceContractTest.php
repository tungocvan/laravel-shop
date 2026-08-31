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

    public function test_header_configuration_is_owned_by_canonical_layout_section(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));
        $config = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config', ['section' => \$section])", $page);
        $this->assertStringContainsString("Route::get('/header', [AdminController::class, 'layoutHeader'])->name('header');", $routes);
        $this->assertStringContainsString("'header'", $config);
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/pages/admin/header/index.blade.php'));
    }

    public function test_legacy_themes_url_redirects_to_canonical_layout_design_route(): void
    {
        $routes = file_get_contents(base_path('Modules/Admin/routes/web.php'));

        $this->assertStringContainsString("Route::get('/themes', fn () => redirect()->route('admin.layout.design'))", $routes);
        $this->assertStringContainsString("->name('themes');", $routes);
        $this->assertStringContainsString("Route::get('/design', [AdminController::class, 'layoutDesign'])->name('design');", $routes);
    }
}
