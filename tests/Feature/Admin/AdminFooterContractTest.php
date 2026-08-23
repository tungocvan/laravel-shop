<?php

namespace Tests\Feature\Admin;

use Modules\Admin\Services\AdminFooterService;
use Tests\TestCase;

class AdminFooterContractTest extends TestCase
{
    public function test_footer_defaults_preserve_current_hidden_ui_baseline(): void
    {
        $config = require base_path('Modules/Admin/config/admin.php');

        $this->assertFalse($config['layout']['show_footer']);
        $this->assertTrue($config['footer']['show_app_name']);
        $this->assertTrue($config['footer']['show_environment']);
    }

    public function test_footer_service_builds_a_prepared_component_registry(): void
    {
        $service = app(AdminFooterService::class);
        $context = $service->context();

        $this->assertArrayHasKey('enabled', $context);
        $this->assertArrayHasKey('components', $context);
        $this->assertSame(['app_name', 'environment'], array_column($context['components'], 'key'));

        foreach ($context['components'] as $component) {
            $this->assertArrayHasKey('view', $component);
            $this->assertArrayHasKey('data', $component);
        }
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

    public function test_footer_config_is_part_of_layout_manager_contract(): void
    {
        $manager = file_get_contents(base_path('Modules/Admin/Support/AdminLayoutManager.php'));

        $this->assertStringContainsString("'footer' => [", $manager);
        $this->assertStringContainsString("'show_app_name' =>", $manager);
        $this->assertStringContainsString("'show_environment' =>", $manager);
    }

    public function test_admin_layout_settings_expose_and_validate_footer_controls(): void
    {
        $component = file_get_contents(base_path('Modules/Admin/Livewire/Settings/AdminLayoutConfig.php'));
        $view = file_get_contents(base_path('Modules/Admin/resources/views/livewire/settings/admin-layout-config.blade.php'));

        $this->assertStringContainsString("'config.layout.show_footer' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.show_app_name' => 'boolean'", $component);
        $this->assertStringContainsString("'config.footer.show_environment' => 'boolean'", $component);

        $this->assertStringContainsString('>Footer</h2>', $view);
        $this->assertStringContainsString("'config.layout.show_footer' => 'Hiển thị Footer'", $view);
        $this->assertStringContainsString("'config.footer.show_app_name' => 'Hiển thị tên ứng dụng'", $view);
        $this->assertStringContainsString("'config.footer.show_environment' => 'Hiển thị môi trường'", $view);
    }
}
