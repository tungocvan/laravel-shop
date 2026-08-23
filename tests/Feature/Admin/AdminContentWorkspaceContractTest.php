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

    public function test_themes_page_adopts_shared_workspace_primitives(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/themes.blade.php'));

        $this->assertStringContainsString('<x-admin::page-header', $view);
        $this->assertStringContainsString('<x-admin::content-section>', $view);
        $this->assertStringNotContainsString('max-w-7xl mx-auto py-6', $view);
        $this->assertStringNotContainsString('text-2xl font-bold text-gray-900', $view);
        $this->assertStringContainsString("@livewire('admin.theme-switcher')", $view);
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
}
