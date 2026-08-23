<?php

namespace Tests\Feature\Admin;

use Tests\TestCase;

class AdminGeneralLayoutContractTest extends TestCase
{
    public function test_general_layout_defaults_expose_bounded_spacing_surface_and_behavior_tokens(): void
    {
        $config = file_get_contents(base_path('Modules/Admin/config/admin.php'));
        foreach (['content_padding_x', 'content_padding_top', 'content_padding_bottom', 'section_gap', 'tablet_padding_x', 'mobile_padding_x'] as $key) $this->assertStringContainsString("'{$key}'", $config);
        foreach (['page_background', 'content_surface', 'border', 'radius', 'reduced_motion'] as $key) $this->assertStringContainsString("'{$key}'", $config);
    }

    public function test_layout_manager_normalizes_general_tokens_against_safe_scales(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));
        $this->assertStringContainsString("private const SPACING_SCALE = ['0', '1', '2', '3', '4', '5', '6', '8', '10', '12']", $manager);
        $this->assertStringContainsString("['system', 'white', 'slate-50']", $manager);
        $this->assertStringContainsString("['transparent', 'system', 'white']", $manager);
        $this->assertStringContainsString("['system', 'none']", $manager);
        $this->assertStringContainsString("['none', 'sm', 'md', 'lg']", $manager);
        $this->assertStringContainsString('private function spacing(', $manager);
    }

    public function test_general_settings_validation_and_reset_include_new_layout_tokens(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $this->assertStringContainsString("\$spacing = 'required|in:0,1,2,3,4,5,6,8,10,12'", $component);
        $this->assertStringContainsString("'config.layout.spacing.content_padding_x' => \$spacing", $component);
        $this->assertStringContainsString("'config.layout.surface.page_background'", $component);
        $this->assertStringContainsString("'config.layout.behavior.reduced_motion' => 'boolean'", $component);
        $this->assertStringContainsString("'spacing' => data_get(\$config, 'layout.spacing', [])", $component);
        $this->assertStringContainsString("'surface' => data_get(\$config, 'layout.surface', [])", $component);
        $this->assertStringContainsString("'behavior' => data_get(\$config, 'layout.behavior', [])", $component);
    }

    public function test_general_save_and_reset_refresh_shell_without_manual_reload(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $this->assertStringContainsString("in_array(\$this->section, ['general', 'header'], true)", $component);
        $this->assertStringContainsString("session()->flash('success'", $component);
        $this->assertStringContainsString("session()->flash('warning'", $component);
        $this->assertStringContainsString("\$this->redirect(url()->previous(), navigate: false)", $component);
    }

    public function test_shell_presentation_maps_general_tokens_to_runtime_css_variables(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminShellPresentationService.php'));
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));
        foreach (['--admin-content-padding-x', '--admin-content-padding-x-tablet', '--admin-content-padding-x-mobile', '--admin-content-padding-top', '--admin-content-padding-bottom', '--admin-section-gap', '--admin-content-surface', '--admin-layout-border', '--admin-layout-radius', '--admin-page-background'] as $variable) $this->assertStringContainsString($variable, $service);
        $this->assertStringContainsString("['shell_style']", $shell); $this->assertStringContainsString('var(--admin-page-background)', $shell); $this->assertStringContainsString('data-admin-reduced-motion', $shell);
        $this->assertStringContainsString('id="admin-content-workspace"', $content); $this->assertStringContainsString('id="admin-container-boundary"', $content); $this->assertStringContainsString("['container_class']", $content); $this->assertStringContainsString('data-admin-container-boundary', $content); $this->assertStringContainsString("['content_style']", $content); $this->assertStringContainsString('var(--admin-content-padding-x-mobile)', $content); $this->assertStringContainsString('var(--admin-content-padding-x-tablet)', $content); $this->assertStringContainsString('var(--admin-content-padding-x)', $content); $this->assertStringContainsString('#admin-container-boundary > * + *', $content); $this->assertStringContainsString('var(--admin-section-gap)', $content);
    }

    public function test_general_workspace_uses_explicit_mobile_tablet_and_desktop_padding_breakpoints(): void
    {
        $content = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/content.blade.php'));
        $this->assertStringContainsString('@media (min-width: 640px)', $content); $this->assertStringContainsString('@media (min-width: 1024px)', $content); $this->assertStringContainsString('var(--admin-content-padding-x-mobile)', $content); $this->assertStringContainsString('var(--admin-content-padding-x-tablet)', $content); $this->assertStringContainsString('var(--admin-content-padding-x)', $content);
    }

    public function test_container_modes_have_distinct_explicit_boundaries(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminShellPresentationService.php'));
        $this->assertStringContainsString("'full' => 'w-full max-w-none'", $service); $this->assertStringContainsString("'narrow' => 'w-full max-w-[60rem] mx-auto'", $service); $this->assertStringContainsString("'7xl' => 'w-full max-w-7xl mx-auto'", $service); $this->assertStringContainsString("'w-full max-w-screen-2xl mx-auto'", $service);
    }

    public function test_general_presets_apply_distinct_safe_workspace_starting_values(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php')); $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));
        $this->assertStringContainsString('public function updatedConfigLayoutPreset(', $component); $this->assertStringContainsString('private function generalPreset(', $component); $this->assertStringContainsString("'data-heavy' =>", $component); $this->assertStringContainsString("'container' => 'full'", $component); $this->assertStringContainsString("'density' => 'compact'", $component); $this->assertStringContainsString("'focus' =>", $component); $this->assertStringContainsString("'container' => 'narrow'", $component); $this->assertStringContainsString("'settings' =>", $component); $this->assertStringContainsString("'container' => '7xl'", $component); $this->assertStringContainsString("'container' => 'screen-2xl'", $component); $this->assertStringContainsString('wire:model.live="config.layout.preset"', $view);
    }

    public function test_general_settings_ui_groups_workspace_spacing_surface_behavior_and_language(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));
        foreach (['Workspace', 'Content spacing', 'Surface', 'Behavior', 'Language & display', 'Workspace preview'] as $heading) $this->assertStringContainsString($heading, $view);
        $this->assertStringContainsString('wire:model.live="config.layout.container"', $view); $this->assertStringContainsString('Padding ngang Desktop', $view); $this->assertStringContainsString('Padding ngang Tablet', $view); $this->assertStringContainsString('Padding ngang Mobile', $view); $this->assertStringContainsString('Page background', $view); $this->assertStringContainsString('Content surface', $view); $this->assertStringContainsString('Reduced motion', $view);
    }

    public function test_general_preview_updates_live_for_container_spacing_and_surface_controls(): void
    {
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));
        $this->assertStringContainsString("\$previewWidths", $view); $this->assertStringContainsString("\$previewSpace", $view); $this->assertStringContainsString("\$previewContainer", $view); $this->assertStringContainsString('wire:model.live="config.layout.spacing.', $view); $this->assertStringContainsString('wire:model.live="config.layout.surface.page_background"', $view); $this->assertStringContainsString('wire:model.live="config.layout.surface.content_surface"', $view); $this->assertStringContainsString('wire:model.live="config.layout.surface.border"', $view); $this->assertStringContainsString('wire:model.live="config.layout.surface.radius"', $view); $this->assertStringContainsString('>Live</span>', $view); $this->assertStringContainsString('Preview cập nhật trước khi lưu.', $view); $this->assertStringNotContainsString('A4 live preview', $view);
    }
}
