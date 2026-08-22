<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageSectionAdminUiConfigurationTest extends TestCase
{
    public function test_homepage_admin_uses_registry_cards_and_ui_input_standard(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $registry = file_get_contents(base_path('Modules/Website/Services/HomepageSectionRegistry.php'));
        $shell = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));
        $builder = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/layout-builder.blade.php'));
        $editor = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/section-editor.blade.php'));
        $view = $shell."\n".$builder."\n".$editor;

        $this->assertStringContainsString("'sectionCards' => \$registry->adminCards(\$this->sectionOrder, \$this->sectionTypes)", $component);
        $this->assertStringContainsString('public function adminCards(array $sectionOrder, array $sectionTypes = [])', $registry);
        $this->assertStringContainsString("'admin' => \$this->adminAction(\$sectionKey)", $registry);

        $this->assertStringContainsString("@include('Website::livewire.admin.home.partials.layout-builder')", $shell);
        $this->assertStringContainsString("@include('Website::livewire.admin.home.partials.section-editor')", $shell);
        $this->assertStringContainsString('@foreach($sectionCards as $card)', $builder);
        $this->assertStringContainsString("\$card['admin']['type'] === 'route'", $builder);
        $this->assertStringContainsString("\$set('activeTab', '{{ \$card['admin']['tab'] }}')", $builder);
        $this->assertStringContainsString("{{ \$card['admin']['label'] }}", $builder);
        $this->assertStringNotContainsString('$sections = [', $view);

        $this->assertStringContainsString('border border-gray-300 bg-white px-3 py-2.5', $view);
        $this->assertStringContainsString('focus:ring-2 focus:ring-indigo-100', $view);
        $this->assertStringContainsString('sticky bottom-4', $shell);
        $this->assertStringContainsString('border border-dashed border-gray-300', $editor);
        $this->assertStringContainsString('home-settings-v3', $component);
    }
}
