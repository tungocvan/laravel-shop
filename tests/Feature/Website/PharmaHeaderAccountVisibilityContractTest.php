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
        $this->assertStringNotContainsString('wire:click="saveAccountSettings"', $admin);
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
        $this->assertStringContainsString('loadBuilderLayout($this->defaultLayout())', $hub);
        $this->assertStringContainsString("'account_settings' =>", $hub);
        $this->assertStringContainsString("'topbar_settings' =>", $hub);
        $this->assertStringContainsString("normalizeAccountSettings(\$theme['account_settings'])", $hub);
        $this->assertStringContainsString("normalizeTopbarSettings(\$theme['topbar_settings'])", $hub);
        $this->assertStringContainsString('normalizePharmaBuilder();', $hub);

    }

    public function test_active_theme_publish_includes_account_and_marks_active_profile(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $library = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php'));

        $this->assertStringContainsString('public function publishTheme(', $hub);
        $this->assertStringContainsString("'website.pharma_header.account' => \$this->accountSettings", $hub);
        $this->assertStringContainsString("'website.pharma_header.topbar' => \$this->topbarSettings", $hub);
        $this->assertStringContainsString("\$this->settingKey('active_theme') => \$this->selectedTheme", $hub);
        $this->assertStringContainsString('public string $activeTheme', $hub);
        $this->assertStringContainsString('wire:click="activateSelectedTheme"', $library);
        $this->assertStringContainsString('Profile website đang sử dụng', $library);
        $this->assertStringContainsString('Cập nhật tham số profile', $library);
    }

    public function test_starter_origin_and_active_only_profile_updates_are_visible(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $library = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php'));

        $this->assertStringContainsString("'starter_profile' =>", $hub);
        $this->assertStringContainsString('public string $starterProfile', $hub);
        $this->assertStringContainsString('activeTheme !== $this->selectedTheme', $hub);
        $this->assertStringContainsString('Đang áp dụng qua', $library);
        $this->assertStringContainsString('Đang xem trước', $library);
        $this->assertStringContainsString("activeTheme !== \$selectedTheme", $library);
    }

    public function test_theme_activation_is_one_action_and_account_is_profile_only(): void
    {
        $hub = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $library = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/partials/theme-manager.blade.php'));
        $admin = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));

        $this->assertStringContainsString('function activateSelectedTheme(', $hub);
        $this->assertStringContainsString('wire:click="activateSelectedTheme"', $library);
        $this->assertStringNotContainsString('wire:click="publishTheme"', $library);
        $this->assertStringNotContainsString('wire:click="saveAccountSettings"', $admin);
    }

}
