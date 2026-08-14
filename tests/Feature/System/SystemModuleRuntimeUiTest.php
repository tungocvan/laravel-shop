<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemModuleRuntimeUiTest extends TestCase
{
    public function test_modules_form_reads_effective_state_from_registry(): void
    {
        $source = file_get_contents(base_path('Modules/System/Livewire/Settings/ModulesForm.php'));

        $this->assertStringContainsString("config('modules.registry', [])", $source);
        $this->assertStringContainsString("'enabled' => (bool) (\$module['enabled'] ?? false)", $source);
        $this->assertStringContainsString("'source' => \$module['source'] ?? ''", $source);
        $this->assertStringNotContainsString("config/module.php", $source);
        $this->assertStringNotContainsString("Config/module.php", $source);
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
