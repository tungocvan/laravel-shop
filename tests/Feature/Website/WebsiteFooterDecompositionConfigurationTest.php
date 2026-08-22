<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterDecompositionConfigurationTest extends TestCase
{
    public function test_footer_partial_is_an_orchestration_shell(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));

        foreach ([
            'components.footer.brand-contact',
            'components.footer.menu-columns',
            'components.footer.app-social',
            'components.footer.bottom-bar',
            'components.footer.back-to-top',
        ] as $view) {
            $this->assertStringContainsString($view, $footer);
        }

        $this->assertStringContainsString("@livewire('website.chat.chat-widget')", $footer);
        $this->assertStringNotContainsString('Privacy Policy', $footer);
        $this->assertStringNotContainsString('upload.wikimedia.org', $footer);
    }

    public function test_footer_components_keep_existing_runtime_contracts(): void
    {
        $brand = file_get_contents(base_path('Modules/Website/resources/views/components/footer/brand-contact.blade.php'));
        $columns = file_get_contents(base_path('Modules/Website/resources/views/components/footer/menu-columns.blade.php'));
        $appSocial = file_get_contents(base_path('Modules/Website/resources/views/components/footer/app-social.blade.php'));
        $bottom = file_get_contents(base_path('Modules/Website/resources/views/components/footer/bottom-bar.blade.php'));

        $this->assertStringContainsString('$footerSettings', $brand);
        $this->assertStringContainsString('$footerColumns', $columns);
        $this->assertStringContainsString('$socialLinks', $appSocial);
        $this->assertStringContainsString("@include('Website::partials.pwa-installer')", $appSocial);
        $this->assertStringContainsString('Privacy Policy', $bottom);
        $this->assertStringContainsString('Visa_Inc._logo.svg', $bottom);
    }
}
