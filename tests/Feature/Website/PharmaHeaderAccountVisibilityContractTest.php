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
        $this->assertStringContainsString('id="header-actions-panel"', $admin);
        $this->assertStringContainsString('Hành động Header', $admin);
        $this->assertStringContainsString("saveAccountSettings", $admin);
        $this->assertSame(2, substr_count($frontend, "@if((\$accountConfig['enabled'] ?? true))"));
        $this->assertStringContainsString("Website::partials.pharma.account-dropdown", $frontend);
        $this->assertStringContainsString("Website::partials.pharma.mobile-account-links", $frontend);
    }
    public function test_pharma_starter_themes_are_preview_only_and_keep_account_and_menu_content(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $library = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php'));

        $this->assertStringContainsString('function previewStarterTheme(', $hub);
        $this->assertStringContainsString("'pharma-classic'", $hub);
        $this->assertStringContainsString("'flexbiz-modern'", $hub);
        $this->assertStringContainsString("'inherit_colors' => false", $hub);
        $this->assertStringNotContainsString('persistThemes(', substr($hub, strpos($hub, 'public function previewStarterTheme('), strpos($hub, 'public function applyTheme(') - strpos($hub, 'public function previewStarterTheme(')));
        $this->assertStringContainsString("previewStarterTheme('pharma-classic')", $library);
        $this->assertStringContainsString("previewStarterTheme('flexbiz-modern')", $library);
    }

}
