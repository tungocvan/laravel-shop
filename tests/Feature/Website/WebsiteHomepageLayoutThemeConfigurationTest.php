<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageLayoutThemeConfigurationTest extends TestCase
{
    public function test_homepage_theme_contract_is_layout_presentation_only_and_versioned(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/HomepageLayoutThemeService.php'));
        $concern = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/Concerns/ManagesHomepageLayoutThemes.php'));
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/layout-themes.blade.php'));
        $wrapper = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));

        $this->assertStringContainsString("public const SETTING_KEY = 'homepage.layout_themes'", $service);
        $this->assertStringContainsString("'schema' => 'flexbiz.homepage-layout-theme'", $service);
        $this->assertStringContainsString("'section_order'", $service);
        $this->assertStringContainsString("'visibility'", $service);
        $this->assertStringContainsString("'section_types'", $service);
        $this->assertStringContainsString("'presentation'", $service);
        $this->assertStringContainsString('array_diff(array_keys($theme), $allowedThemeKeys)', $service);
        $this->assertStringNotContainsString("'category_ids'", $service);
        $this->assertStringNotContainsString("'featured_ids'", $service);
        $this->assertStringNotContainsString("'promo_banner'", $service);
        $this->assertStringNotContainsString("'newsletter'", $service);
        $this->assertStringNotContainsString("'trust_badges'", $service);

        foreach (['saveTheme', 'applyTheme', 'updateTheme', 'renameTheme', 'deleteTheme', 'exportTheme', 'importTheme'] as $method) {
            $this->assertStringContainsString("function {$method}", $concern);
        }
        $this->assertStringContainsString("authorizeAdminPermission('website.home.manage')", $concern);
        $this->assertStringContainsString('ManagesHomepageLayoutThemes', $component);

        foreach (['wire:click="saveTheme"', 'wire:click="applyTheme"', 'wire:click="updateTheme"', 'wire:click="renameTheme"', 'wire:click="deleteTheme"', 'wire:click="exportTheme"', 'wire:click="importTheme"'] as $control) {
            $this->assertStringContainsString($control, $view);
        }
        $this->assertStringContainsString('wire:model="themeJson"', $view);
        $this->assertStringContainsString("@include('Website::livewire.admin.home.partials.layout-themes')", $wrapper);
        $this->assertStringContainsString('Preview-first', $view);
        $this->assertStringContainsString('border border-gray-300 bg-white px-3 py-2.5', $view);
    }
}
