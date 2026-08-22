<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteHomepageSingleRootConfigurationTest extends TestCase
{
    public function test_newsletter_signup_keeps_style_inside_single_livewire_root(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/home/newsletter-signup.blade.php'));

        $rootClose = strrpos($view, '</div>');
        $style = strpos($view, '<style>');

        $this->assertNotFalse($rootClose);
        $this->assertNotFalse($style);
        $this->assertLessThan($rootClose, $style);
        $this->assertStringNotContainsString("</div>\n\n{{-- 3. CSS ANIMATION", $view);
    }
}
