<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageSectionAdminUiConfigurationTest extends TestCase
{
    public function test_homepage_admin_uses_registry_cards_and_ui_input_standard(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $registry = file_get_contents(base_path('Modules/Website/Services/HomepageSectionRegistry.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v2.blade.php'));
        $legacyView = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings.blade.php'));

        $this->assertStringContainsString("'sectionCards' => \$registry->adminCards(\$this->sectionOrder, \$this->sectionTypes)", $component);
        $this->assertStringContainsString('public function adminCards(array $sectionOrder, array $sectionTypes = [])', $registry);
        $this->assertStringContainsString("'admin' => \$this->adminAction(\$sectionKey)", $registry);

        $this->assertStringContainsString('@foreach($sectionCards as $card)', $view);
        $this->assertStringContainsString("\$card['admin']['type'] === 'route'", $view);
        $this->assertStringContainsString("wire:click=\"setTab('{{ \$card['admin']['tab'] }}')\"", $view);
        $this->assertStringContainsString("{{ \$card['admin']['label'] }}", $view);
        $this->assertStringNotContainsString('$sections = [', $view);

        $this->assertStringContainsString('border border-gray-300 bg-white px-3 py-2.5', $view);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $view);
        $this->assertStringContainsString('sticky bottom-4', $view);
        $this->assertStringContainsString('border border-dashed border-gray-300', $view);

        $this->assertStringContainsString('home-settings-v2', $component);
        $this->assertStringContainsString('$sections = [', $legacyView);
    }
}
