<?php

namespace Tests\Feature\System;

use Illuminate\Support\Facades\Route;
use InvalidArgumentException;
use Modules\System\Services\SystemScriptOperationService;
use Tests\TestCase;

class SystemScriptOperationsTest extends TestCase
{
    public function test_script_operation_registry_is_empty_until_scripts_are_explicitly_approved(): void
    {
        $this->assertSame([], app(SystemScriptOperationService::class)->operations());
    }

    public function test_unknown_script_operation_is_rejected(): void
    {
        $this->expectException(InvalidArgumentException::class);

        app(SystemScriptOperationService::class)->execute('arbitrary-script', 123);
    }

    public function test_livewire_component_has_no_browser_shell_editor_or_direct_execution(): void
    {
        $contents = file_get_contents(base_path('Modules/System/Livewire/Settings/ShScript.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $contents);
        $this->assertStringContainsString("authorizePermission('system.commands.run')", $contents);
        $this->assertStringContainsString('SystemScriptOperationService', $contents);

        foreach ([
            'scriptContent',
            'newScriptName',
            'selectedScript',
            'File::put',
            'File::delete',
            'chmod(',
            'shell_exec(',
        ] as $forbidden) {
            $this->assertStringNotContainsString($forbidden, $contents);
        }
    }

    public function test_script_ui_does_not_expose_create_edit_delete_or_free_form_shell_content(): void
    {
        $contents = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/sh-script.blade.php'));

        $this->assertStringContainsString('executeOperation', $contents);
        $this->assertStringContainsString('Chưa có script nào được phê duyệt', $contents);
        $this->assertStringNotContainsString('saveScript', $contents);
        $this->assertStringNotContainsString('deleteScript', $contents);
        $this->assertStringNotContainsString('executeScript', $contents);
        $this->assertStringNotContainsString('scriptContent', $contents);
        $this->assertStringNotContainsString('newScriptName', $contents);
        $this->assertStringNotContainsString('<textarea', $contents);
    }

    public function test_script_service_uses_fixed_process_execution_and_has_safety_controls(): void
    {
        $contents = file_get_contents(base_path('Modules/System/Services/SystemScriptOperationService.php'));

        $this->assertStringContainsString('new Process(', $contents);
        $this->assertStringContainsString("['/bin/bash', \$scriptPath]", $contents);
        $this->assertStringContainsString('setTimeout(', $contents);
        $this->assertStringContainsString('MAX_OUTPUT_BYTES', $contents);
        $this->assertStringContainsString("app_path('sh')", $contents);
        $this->assertStringContainsString("str_contains(\$relativePath, '..')", $contents);
        $this->assertStringContainsString('is_readable($candidate)', $contents);
        $this->assertStringNotContainsString('shell_exec(', $contents);
        $this->assertStringNotContainsString('exec(', $contents);
    }

    public function test_scripts_admin_route_has_expected_authorization(): void
    {
        $route = Route::getRoutes()->getByName('admin.system.scripts');

        $this->assertNotNull($route);
        $this->assertSame('admin/system/scripts', $route->uri());
        $middleware = $route->gatherMiddleware();
        $this->assertContains('auth:admin', $middleware);
        $this->assertContains('permission:system.commands.run,admin', $middleware);
    }

    public function test_scripts_admin_page_mounts_restricted_livewire_component(): void
    {
        $contents = file_get_contents(base_path('Modules/System/resources/views/pages/settings/scripts.blade.php'));

        $this->assertStringContainsString('<livewire:system.settings.sh-script />', $contents);
    }

    public function test_scripts_admin_menu_uses_command_permission(): void
    {
        $menus = json_decode(
            file_get_contents(base_path('Modules/Admin/data/menus.json')),
            true,
            flags: JSON_THROW_ON_ERROR,
        );

        $systemMenu = collect($menus)->firstWhere('name', 'Công cụ Hệ thống');
        $this->assertNotNull($systemMenu);

        $scriptsMenu = collect($systemMenu['children'] ?? [])->firstWhere('url', '/admin/system/scripts');
        $this->assertNotNull($scriptsMenu);
        $this->assertSame('Thao tác Script', $scriptsMenu['name']);
        $this->assertSame('system.commands.run', $scriptsMenu['can']);
        $this->assertTrue((bool) ($scriptsMenu['is_active'] ?? false));
    }

    public function test_production_containment_still_forces_sh_script_component_disabled(): void
    {
        $contents = file_get_contents(base_path('Modules/System/Services/SystemConfigService.php'));
        $tabs = require base_path('Modules/System/config/system_tabs.php');
        $tab = collect($tabs)->firstWhere('component', 'system.settings.sh-script');

        $this->assertStringContainsString("'system.settings.sh-script'", $contents);
        $this->assertNotNull($tab);
        $this->assertFalse((bool) ($tab['enabled'] ?? true));
    }
}
