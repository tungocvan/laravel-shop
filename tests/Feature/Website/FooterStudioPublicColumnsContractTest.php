<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class FooterStudioPublicColumnsContractTest extends TestCase
{
    public function test_published_profile_columns_replace_legacy_primary_slots(): void
    {
        $resolver = file_get_contents(base_path('Modules/Website/Services/FooterProfileResolver.php'));
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer-shared.blade.php'));
        $column = file_get_contents(base_path('Modules/Website/resources/views/components/footer/profile-column.blade.php'));
        $this->assertStringContainsString("'profile_columns_active' => \$profileColumnsActive", $resolver);
        $this->assertStringContainsString("'footerProfileColumnsActive'=>\$profileData['profile_columns_active']", $provider);
        $this->assertStringContainsString('@if($footerProfileColumnsActive ?? false)', $footer);
        $this->assertStringContainsString('components.footer.profile-column', $footer);
        $this->assertStringContainsString("@case('brand')", $column);
        $this->assertStringContainsString("@case('contact')", $column);
        $this->assertStringContainsString("@case('app-install')", $column);
    }

    public function test_editor_initializes_active_profile_and_preview_does_not_duplicate_slots(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterSettingsHub.php'));
        $preview = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/partials/builder-preview.blade.php'));
        $this->assertStringContainsString('private function loadActiveProfileDraft()', $component);
        $this->assertStringContainsString("\$this->selectedTheme = \$this->activeTheme;", $component);
        $this->assertStringContainsString("columnsSource === 'profile'", $preview);
        $this->assertStringContainsString("column['enabled']", $preview);
    }
}
