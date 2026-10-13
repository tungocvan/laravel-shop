<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaHeaderBuilderContractTest extends TestCase
{
    #[Test]
    public function pharma_header_uses_published_template_specific_builder_settings(): void
    {
        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $this->assertStringContainsString('website.pharma_header.layout', $header);
        $this->assertStringContainsString('website.pharma_header.presentation', $header);
        $this->assertStringContainsString('HeaderLayoutService', $header);
        $this->assertStringContainsString("component['type'] === 'actions'", $header);
        $this->assertStringNotContainsString("route('cart", $header);
    }

    #[Test]
    public function header_builder_preserves_default_settings_and_separates_pharma(): void
    {
        $builder = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/HeaderSettingsHub.php'));
        $this->assertStringContainsString("'website.pharma_header.'.\$suffix", $builder);
        $this->assertStringContainsString("'header.'.\$suffix", $builder);
        $this->assertStringContainsString("get('website.public_template', 'default')", $builder);
        $this->assertStringContainsString('updatedEditingTemplate', $builder);
        $this->assertStringContainsString("settingKey('layout')", $builder);
        $this->assertStringContainsString("settingKey('layout_themes')", $builder);
    }

    #[Test]
    public function pharma_header_builder_has_independent_default_layout(): void
    {
        $this->assertIsArray(config('website.header.pharma_layout'));
        $this->assertSame('brand', data_get(config('website.header.pharma_layout'), 'desktop.main.left.0.type'));
        $this->assertSame('actions', data_get(config('website.header.pharma_layout'), 'desktop.main.center.0.type'));
    }
    #[Test]
    public function pharma_navigation_is_managed_separately_from_default_menu(): void
    {
        $locations = (array) config('website.header.menu_locations', []);
        $this->assertArrayHasKey('primary', $locations);
        $this->assertArrayHasKey('pharma_primary', $locations);
        $this->assertArrayHasKey('pharma_mobile', $locations);

        $manager = file_get_contents(base_path('Modules/Website/Livewire/Admin/Header/MenuManager.php'));
        $this->assertStringContainsString('pharma_primary', $manager);
        $this->assertStringContainsString('availableLocations', $manager);

        $hub = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/header/header-settings-hub.blade.php'));
        $this->assertStringContainsString("'editingTemplate' => " . '$editingTemplate', $hub);

        $header = file_get_contents(base_path('Modules/Website/resources/views/partials/pharma/header.blade.php'));
        $this->assertStringContainsString("getMenuTreeByLocation('pharma_primary')", $header);
        $this->assertStringContainsString("getMenuTreeByLocation('pharma_mobile')", $header);
    }

}
