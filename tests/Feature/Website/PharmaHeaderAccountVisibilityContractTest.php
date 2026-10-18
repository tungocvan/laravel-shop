<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class PharmaHeaderAccountVisibilityContractTest extends TestCase
{
    public function test_pharma_account_visibility_is_persisted_and_gates_both_surfaces(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $frontend = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));

        $this->assertStringContainsString("'enabled' => (bool) (\$values['enabled'] ?? true)", $hub);
        $this->assertStringContainsString("wire:model=\"accountSettings.enabled\"", $admin);
        $this->assertStringContainsString("saveAccountSettings", $admin);
        $this->assertSame(2, substr_count($frontend, "@if((\$accountConfig['enabled'] ?? true))"));
        $this->assertStringContainsString("Website::partials.pharma.account-dropdown", $frontend);
        $this->assertStringContainsString("Website::partials.pharma.mobile-account-links", $frontend);
    }
}
