<?php

namespace Tests\Feature\System;

use App\Modules\ModuleLifecycleManager;
use Modules\System\Livewire\Settings\ModulesForm;
use Modules\System\Services\SystemModuleControlService;
use ReflectionClass;
use Tests\TestCase;

class SystemModuleRuntimeLifecycleTest extends TestCase
{
    public function test_browser_module_archive_action_is_retired(): void
    {
        $component = new ReflectionClass(ModulesForm::class);

        $this->assertFalse($component->hasMethod('deleteModule'));

        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/modules-form.blade.php'));
        $this->assertStringNotContainsString('wire:click="deleteModule', $view);
        $this->assertStringNotContainsString('module-trash', $view);
        $this->assertStringNotContainsString('>Gỡ</button>', $view);
    }

    public function test_runtime_services_cannot_archive_module_source(): void
    {
        $control = new ReflectionClass(SystemModuleControlService::class);
        $lifecycle = new ReflectionClass(ModuleLifecycleManager::class);

        $this->assertFalse($control->hasMethod('archive'));
        $this->assertFalse($lifecycle->hasMethod('archive'));
    }
}
