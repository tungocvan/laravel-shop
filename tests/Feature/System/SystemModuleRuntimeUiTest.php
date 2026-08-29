<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemModuleRuntimeUiTest extends TestCase
{
    public function test_modules_form_delegates_effective_registry_rows_to_overview_service(): void
    {
        $form = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));
        $overview = file_get_contents(base_path('Modules/System/Services/SystemModuleOverviewService.php'));
        $registry = file_get_contents(base_path('app/Modules/ModuleRegistry.php'));

        $this->assertStringContainsString('SystemModuleOverviewService::class', $form);
        $this->assertStringContainsString('->current()', $overview);
        $this->assertStringContainsString("'enabled' => (bool) (\$module['enabled'] ?? false)", $overview);
        $this->assertStringContainsString("'source' => \$module['source'] ?? ''", $overview);
        $this->assertStringContainsString("config('modules.registry', [])", $registry);
        $this->assertStringNotContainsString('config/module.php', $overview);
        $this->assertStringNotContainsString('Config/module.php', $overview);
    }

    public function test_modules_view_distinguishes_runtime_and_manifest_state_sources(): void
    {
        $source = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/modules-form.blade.php'));

        $this->assertStringContainsString("\$module['source'] === 'runtime'", $source);
        $this->assertStringContainsString('Runtime', $source);
        $this->assertStringContainsString('Manifest', $source);
    }

    public function test_modules_view_documents_runtime_state_storage(): void
    {
        $source = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/modules-form.blade.php'));

        $this->assertStringContainsString('storage/app/system/module-state.json', $source);
        $this->assertStringContainsString('không sửa manifest', $source);
    }
}
