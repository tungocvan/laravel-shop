<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\AdminThemeProfileService;
use Tests\TestCase;

class AdminThemeEditorContractTest extends TestCase
{
    public function test_design_route_uses_dedicated_theme_editor(): void
    {
        $page = file_get_contents(base_path('Modules/Admin/resources/views/pages/admin/layout-section.blade.php'));

        $this->assertStringContainsString("\$section === 'design'", $page);
        $this->assertStringContainsString("@livewire('admin.settings.admin-theme-editor')", $page);
        $this->assertStringContainsString("@livewire('admin.settings.admin-layout-config', ['section' => \$section])", $page);
    }

    public function test_theme_profiles_cover_whole_admin_shell_and_have_professional_default(): void
    {
        $service = app(AdminThemeProfileService::class);
        $profiles = $service->profiles();
        $default = $profiles[AdminThemeProfileService::DEFAULT_PROFILE];

        $this->assertSame('professional-indigo', AdminThemeProfileService::DEFAULT_PROFILE);
        $this->assertSame('Professional Indigo', $default['label']);
        $this->assertTrue($default['built_in']);
        $this->assertSame('soft-light', data_get($default, 'payload.theme.default'));
        $this->assertSame('indigo-600', data_get($default, 'payload.design.colors.accent'));
        $this->assertSame('system', data_get($default, 'payload.header.presentation.background'));
        $this->assertSame('system', data_get($default, 'payload.footer.presentation.background'));
        $this->assertSame('theme', data_get($default, 'payload.sidebar.presentation.background'));
        $this->assertArrayHasKey('corporate-blue', $profiles);
        $this->assertArrayHasKey('modern-dark', $profiles);
        $this->assertArrayHasKey('warm-sunset', $profiles);
    }

    public function test_theme_profile_payload_does_not_capture_content_or_behavior_settings(): void
    {
        $service = app(AdminThemeProfileService::class);
        $payload = $service->extractPayload([
            'design' => ['colors' => ['accent' => 'indigo-600']],
            'theme' => ['default' => 'soft-light', 'accent' => 'indigo'],
            'layout' => ['surface' => ['page_background' => 'system']],
            'sidebar' => ['expanded_width' => '320px', 'header' => ['title' => 'Do not save'], 'presentation' => ['background' => 'theme']],
            'header' => ['brand' => ['title' => 'Do not save'], 'presentation' => ['background' => 'system']],
            'footer' => ['copyright' => ['owner' => 'Do not save'], 'presentation' => ['background' => 'system']],
        ]);

        $this->assertArrayHasKey('design', $payload);
        $this->assertArrayHasKey('theme', $payload);
        $this->assertArrayHasKey('layout', $payload);
        $this->assertArrayHasKey('sidebar', $payload);
        $this->assertArrayHasKey('header', $payload);
        $this->assertArrayHasKey('footer', $payload);
        $this->assertArrayNotHasKey('expanded_width', $payload['sidebar']);
        $this->assertArrayNotHasKey('header', $payload['sidebar']);
        $this->assertArrayNotHasKey('brand', $payload['header']);
        $this->assertArrayNotHasKey('copyright', $payload['footer']);
    }

    public function test_theme_editor_exposes_select_edit_save_as_and_restore_flows(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminThemeEditor.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-theme-editor.blade.php'));

        foreach (['selectTheme(', 'saveTheme(', 'saveAsTheme(', 'restoreDefaultTheme('] as $contract) {
            $this->assertStringContainsString($contract, $component);
        }

        foreach (['Theme Editor', 'Semantic colors', 'Sidebar Theme', 'Header presentation', 'Content & Footer', 'Lưu thành Theme mới', 'Khôi phục Theme mặc định', 'Admin preview'] as $label) {
            $this->assertStringContainsString($label, $view);
        }

        $this->assertStringContainsString('config.design.colors.accent', $view);
        $this->assertStringContainsString('config.theme.default', $view);
        $this->assertStringContainsString('config.sidebar.presentation.background', $view);
        $this->assertStringContainsString('config.header.presentation.background', $view);
        $this->assertStringContainsString('config.footer.presentation.background', $view);
        $this->assertStringContainsString('wire:submit="saveTheme"', $view);
        $this->assertStringContainsString('wire:click="saveAsTheme"', $view);
        $this->assertStringContainsString('wire:click="restoreDefaultTheme"', $view);
    }

    public function test_design_service_supports_light_dark_blue_and_warm_theme_tokens(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminDesignService.php'));

        foreach (['slate-950', 'slate-100', 'blue-600', 'blue-500', 'orange-50', 'orange-600', 'indigo-400'] as $token) {
            $this->assertStringContainsString("'{$token}'", $service);
        }

        $this->assertStringContainsString('public function colorOptions(): array', $service);
    }
}
