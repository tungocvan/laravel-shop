<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepagePresentationConfigurationTest extends TestCase
{
    public function test_homepage_presentation_is_resolved_persisted_and_applied(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/HomepagePresentationService.php'));
        $frontend = file_get_contents(base_path('Modules/Website/Livewire/Home/HomeList.php'));
        $frontendView = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/home-list.blade.php'));

        $this->assertStringContainsString("get('homepage.presentation', [])", $component);
        $this->assertStringContainsString("'homepage.presentation' => \$this->presentation", $component);
        $this->assertStringContainsString("'container_width' => 1280", $service);
        $this->assertStringContainsString("'section_gap' => 48", $service);
        $this->assertStringContainsString("get('homepage.presentation', [])", $frontend);
        $this->assertStringContainsString('homepageContainerClass', $frontend);
        $this->assertStringContainsString('--homepage-section-gap', $frontendView);
        $this->assertStringContainsString('--homepage-mobile-section-gap', $frontendView);
    }

    public function test_admin_exposes_responsive_preview_and_advanced_tokens(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $shell = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/presentation-preview.blade.php'));

        $this->assertStringContainsString('home-settings-v3', $component);
        $this->assertStringContainsString("@include('Website::livewire.admin.home.partials.presentation-preview')", $shell);
        $this->assertStringContainsString("previewDevice: 'desktop'", $view);
        $this->assertStringContainsString("previewDevice==='mobile'", $view);
        $this->assertStringContainsString('wire:model.live="presentation.mode"', $view);
        $this->assertStringContainsString('wire:model.live="presentation.container"', $view);
        $this->assertStringContainsString('presentation.custom.container_width', $view);
        $this->assertStringContainsString('presentation.custom.mobile_section_gap', $view);
    }
}
