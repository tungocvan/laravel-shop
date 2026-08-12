<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemRetiredSettingsPlaceholdersTest extends TestCase
{
    public function test_only_truly_unused_settings_placeholder_is_retired(): void
    {
        $this->assertFileExists(base_path('Modules/System/Livewire/Settings/StorageConfig.php'));
        $this->assertFileExists(base_path('Modules/System/resources/views/livewire/settings/storage-config.blade.php'));

        $this->assertFileDoesNotExist(base_path('Modules/System/Livewire/Settings/Placeholder.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/settings/placeholder.blade.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/placeholder.blade.php'));
    }

    public function test_env_controller_preserves_storage_component_runtime_contract(): void
    {
        $controller = file_get_contents(base_path('Modules/System/Http/Controllers/EnvConfigController.php'));
        $storage = file_get_contents(base_path('Modules/System/Livewire/Settings/StorageConfig.php'));

        $this->assertStringContainsString("'component' => 'system.settings.storage-config'", $controller);
        $this->assertStringContainsString('class StorageConfig extends Component', $storage);
        $this->assertStringContainsString("view('System::livewire.settings.storage-config')", $storage);
    }

    public function test_setting_form_does_not_reference_retired_placeholder(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/SettingForm.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/setting-form.blade.php'));

        $this->assertStringNotContainsString('placeholder', strtolower($component));
        $this->assertStringNotContainsString('placeholder', strtolower($view));
    }
}
