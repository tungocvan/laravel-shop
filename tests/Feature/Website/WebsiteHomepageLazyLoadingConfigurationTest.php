<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageLazyLoadingConfigurationTest extends TestCase
{
    public function test_homepage_registry_does_not_enable_livewire_lazy_loading(): void
    {
        $config = file_get_contents(base_path('Modules/Website/Config/homepage.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/home-list.blade.php'));

        $this->assertStringNotContainsString("'lazy' => true", $config);
        $this->assertStringContainsString("@livewire(\$render['renderer'], \$render['params'] ?? [], key('home-'.\$sectionKey))", $view);
    }
}
