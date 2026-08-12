<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemSettingsImagesTest extends TestCase
{
    public function test_images_refactor_security_and_replacement_contract(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/Images.php'));
        $service = file_get_contents(base_path('Modules/System/Services/SettingsService.php'));
        $view = file_get_contents(base_path('Modules/System/resources/views/livewire/settings/partials/images.blade.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $component);
        $this->assertGreaterThanOrEqual(2, substr_count($component, "authorizePermission('system.settings.update')"));
        $this->assertStringNotContainsString('Setting::setValue', $component);
        $this->assertStringContainsString('function get(string $key', $service);
        $this->assertStringContainsString('function set(string $key', $service);
        $this->assertStringContainsString('function replaceImage(', $service);
        $this->assertStringContainsString('function removeImage(', $service);
        $this->assertStringContainsString('$newPath = $upload->store', $service);
        $this->assertStringContainsString('$disk->delete($newPath);', $service);
        $this->assertStringContainsString('$this->set($key, null', $service);
        $this->assertStringContainsString('mimes:png,jpg,jpeg', $component);
        $this->assertStringNotContainsString('image/svg+xml', $view);
        $this->assertStringContainsString('wire:confirm', $view);
    }
}
