<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\AdminFooterService;
use Tests\TestCase;

class AdminFooterContractTest extends TestCase
{
    public function test_footer_defaults_preserve_hidden_baseline_and_professional_content_defaults(): void
    {
        $config = require base_path('Modules/Admin/config/admin.php');

        $this->assertFalse($config['layout']['show_footer']);
        $this->assertTrue($config['footer']['show_app_name']);
        $this->assertSame([
            'enabled' => true,
            'owner' => null,
            'url' => null,
            'start_year' => null,
        ], $config['footer']['copyright']);
        $this->assertSame([
            'show_date' => true,
            'show_time' => true,
            'date_format' => 'd/m/Y',
            'time_format' => 'H:i:s',
        ], $config['footer']['datetime']);
        $this->assertSame('split', $config['footer']['presentation']['alignment']);
        $this->assertTrue($config['footer']['presentation']['compact']);
        $this->assertArrayNotHasKey('show_environment', $config['footer']);
    }

    public function test_footer_service_builds_professional_component_registry(): void
    {
        $context = app(AdminFooterService::class)->context();

        $this->assertArrayHasKey('enabled', $context);
        $this->assertArrayHasKey('presentation', $context);
        $this->assertArrayHasKey('components', $context);
        $this->assertSame(['copyright', 'datetime'], array_column($context['components'], 'key'));
        $this->assertSame('split', $context['presentation']['alignment']);

        foreach ($context['components'] as $component) {
            $this->assertArrayHasKey('view', $component);
            $this->assertArrayHasKey('data', $component);
        }
    }

    public function test_footer_runtime_uses_fixed_safe_date_and_time_formats(): void
    {
        $service = file_get_contents(base_path('Modules/Admin/Services/AdminFooterService.php'));

        $this->assertStringContainsString("\$now->format('d/m/Y')", $service);
        $this->assertStringContainsString("\$now->format('H:i:s')", $service);
        $this->assertStringContainsString("\$this->component('copyright'", $service);
        $this->assertStringContainsString("\$this->component('datetime'", $service);
        $this->assertStringNotContainsString('environment', $service);
    }

    public function test_obsolete_footer_components_are_removed(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/layouts/partials/footer/components/app_name.blade.php'));
        $this->assertFileDoesNotExist(base_path('Modules/Admin/resources/views/layouts/partials/footer/components/environment.blade.php'));
        $this->assertFileExists(base_path('Modules/Admin/resources/views/layouts/partials/footer/components/copyright.blade.php'));
        $this->assertFileExists(base_path('Modules/Admin/resources/views/layouts/partials/footer/components/datetime.blade.php'));
    }

    public function test_footer_root_is_declarative_and_shell_does_not_own_footer_visibility(): void
    {
        $footer = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/footer.blade.php'));
        $shell = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/shell.blade.php'));

        $this->assertStringContainsString('AdminFooterService::class', $footer);
        $this->assertStringContainsString("@foreach (\$adminFooterContext['components'] as \$footerComponent)", $footer);
        $this->assertStringContainsString("@include(\$footerComponent['view'], \$footerComponent['data'])", $footer);
        $this->assertStringNotContainsString("config('app.name'", $footer);
        $this->assertStringNotContainsString('app()->environment()', $footer);
        $this->assertStringContainsString("@include('Admin::layouts.partials.footer')", $shell);
        $this->assertStringNotContainsString("data_get(\$adminLayoutConfig, 'show_footer'", $shell);
    }

    public function test_layout_manager_persists_and_sanitizes_professional_footer_contract(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString("'footer' => \$this->footerDefaults()", $manager);
        $this->assertStringContainsString("'footer' => \$this->normalizeFooter", $manager);
        $this->assertStringContainsString('private function footerDefaults(): array', $manager);
        $this->assertStringContainsString('private function normalizeFooter(array $footer, array $defaults): array', $manager);
        $this->assertStringContainsString("'copyright' => [", $manager);
        $this->assertStringContainsString("'datetime' => [", $manager);
        $this->assertStringContainsString("'presentation' => [", $manager);
        $this->assertStringContainsString("'date_format' => 'd/m/Y'", $manager);
        $this->assertStringContainsString("'time_format' => 'H:i:s'", $manager);
        $this->assertStringContainsString("['split', 'center']", $manager);
        $this->assertStringContainsString("['system', 'transparent']", $manager);
        $this->assertStringNotContainsString("'show_environment' =>", $manager);
    }

    public function test_admin_layout_settings_expose_validate_and_refresh_footer_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-footer-config.blade.php'));

        $this->assertStringContainsString("if (\$this->section === 'footer')", $component);
        $this->assertStringContainsString('admin-footer-config', $component);
        $this->assertStringContainsString("['general', 'header', 'footer']", $component);
        $this->assertStringContainsString("'config.layout.show_footer' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.show_app_name' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.copyright.enabled' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.copyright.owner' => 'nullable|string|max:120'", $component);
        $this->assertStringContainsString("'config.footer.datetime.show_date' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.datetime.show_time' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.presentation.alignment' => 'required|in:split,center'", $component);
        $this->assertStringNotContainsString('config.footer.show_environment', $component);

        $this->assertStringContainsString('config.layout.show_footer', $view);
        $this->assertStringContainsString('Hiển thị Footer', $view);
        $this->assertStringContainsString('config.footer.copyright.owner', $view);
        $this->assertStringContainsString('Tác giả / đơn vị sở hữu', $view);
        $this->assertStringContainsString('config.footer.datetime.show_date', $view);
        $this->assertStringContainsString('config.footer.datetime.show_time', $view);
        $this->assertStringContainsString('Footer preview', $view);
        $this->assertStringContainsString('Lưu Footer', $view);
        $this->assertStringNotContainsString('Hiển thị môi trường', $view);
    }

    public function test_layout_manager_accepts_decoded_json_settings_and_saves_arrays(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString('if (is_array($value))', $manager);
        $this->assertStringContainsString('return $value;', $manager);
        $this->assertMatchesRegularExpression('/Setting::setValue\s*\(\s*self::SETTING_KEY\s*,\s*\$normalized\s*,/s', $manager);
        $this->assertStringNotContainsString('json_encode($normalized', $manager);
    }
}
