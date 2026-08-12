<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class SystemSettingsGeneralTest extends TestCase
{
    public function test_general_settings_refactor_contract(): void
    {
        $component = file_get_contents(base_path('Modules/System/Livewire/Settings/Partials/General.php'));
        $service = file_get_contents(base_path('Modules/System/Services/SettingsService.php'));

        $this->assertStringContainsString('AuthorizesSystemActions', $component);
        $this->assertStringContainsString("authorizePermission('system.settings.update')", $component);
        $this->assertStringContainsString('SettingsService', $component);
        $this->assertStringNotContainsString('Setting::setValue', $component);
        $this->assertStringContainsString('function get(string $key', $service);
        $this->assertStringContainsString('function updateMany(', $service);
        $this->assertStringContainsString('function getGeneral(', $service);
        $this->assertStringContainsString('function saveGeneral(', $service);
        $this->assertStringContainsString('array_intersect_key($values, array_flip(self::GENERAL_KEYS))', $service);
        $this->assertStringContainsString("dispatch('site-name-updated')", $component);
    }
}
