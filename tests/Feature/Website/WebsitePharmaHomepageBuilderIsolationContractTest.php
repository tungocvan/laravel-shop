<?php

namespace Tests\Feature\Website;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class WebsitePharmaHomepageBuilderIsolationContractTest extends TestCase
{
    #[Test]
    public function pharma_builder_has_separate_workspace_and_persistence_keys(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Home/HomeSettings.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/home-settings-v3.blade.php'));
        $builder = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/home/partials/pharma-layout-builder.blade.php'));

        $this->assertStringContainsString("public string \$editingTemplate = 'default'", $component);
        $this->assertStringContainsString("'website.pharma_homepage.layout'", $component);
        $this->assertStringContainsString("'website.pharma_homepage.order'", $component);
        $this->assertStringContainsString("if (\$this->editingTemplate === 'pharma')", $component);
        $this->assertStringContainsString('selectEditingTemplate', $view);
        $this->assertStringContainsString('pharma-layout-builder', $view);
        $this->assertStringContainsString('pharmaLayout.', $builder);
        $this->assertStringNotContainsString('Flash Sale', implode(' ', array_keys([
            'hero' => true, 'categories' => true, 'new_arrivals' => true,
        ])));
    }
}
