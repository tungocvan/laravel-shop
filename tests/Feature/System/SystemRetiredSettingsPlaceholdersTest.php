<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemRetiredSettingsPlaceholdersTest extends TestCase
{
    public function test_unused_settings_placeholders_are_retired(): void
    {
        $this->assertFileDoesNotExist(base_path('Modules/System/Livewire/Settings/StorageConfig.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/settings/storage-config.blade.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/Livewire/Settings/Placeholder.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/settings/placeholder.blade.php'));
        $this->assertFileDoesNotExist(base_path('Modules/System/resources/views/livewire/placeholder.blade.php'));
    }

    public function test_setting_form_does_not_reference_retired_components(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/SettingForm.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/setting-form.blade.php'));

        $this->assertStringNotContainsString('storage-config', strtolower($component));
        $this->assertStringNotContainsString('placeholder', strtolower($component));
        $this->assertStringNotContainsString('storage-config', strtolower($view));
        $this->assertStringNotContainsString('placeholder', strtolower($view));
    }
}
