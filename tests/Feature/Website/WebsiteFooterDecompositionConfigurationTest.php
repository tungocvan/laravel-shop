<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterDecompositionConfigurationTest extends TestCase
{
    public function test_footer_partial_is_an_orchestration_shell(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));

        $this->assertStringContainsString('components.footer.slot', $footer);
        $this->assertStringContainsString("@livewire('website.chat.chat-widget')", $footer);

        foreach ([
            'components.footer.brand-contact',
            'components.footer.menu-columns',
            'components.footer.app-social',
            'components.footer.bottom-bar',
            'components.footer.back-to-top',
        ] as $legacyView) {
            $this->assertStringNotContainsString($legacyView, $footer);
        }

        $this->assertStringNotContainsString('Privacy Policy', $footer);
        $this->assertStringNotContainsString('upload.wikimedia.org', $footer);
    }

    public function test_footer_components_keep_current_runtime_contracts(): void
    {
        $brand = file_get_contents(base_path('Modules/Website/resources/views/components/footer/brand.blade.php'));
        $contact = file_get_contents(base_path('Modules/Website/resources/views/components/footer/contact.blade.php'));
        $columns = file_get_contents(base_path('Modules/Website/resources/views/components/footer/menu-columns.blade.php'));
        $appInstall = file_get_contents(base_path('Modules/Website/resources/views/components/footer/app-install.blade.php'));
        $social = file_get_contents(base_path('Modules/Website/resources/views/components/footer/social-links.blade.php'));
        $legal = file_get_contents(base_path('Modules/Website/resources/views/components/footer/legal-links.blade.php'));
        $trust = file_get_contents(base_path('Modules/Website/resources/views/components/footer/trust-badges.blade.php'));

        $this->assertStringContainsString('$footerSettings', $brand);
        $this->assertStringContainsString('$footerSettings', $contact);
        $this->assertStringContainsString('$footerColumns', $columns);

        $this->assertStringContainsString("@include('Website::partials.pwa-installer', [", $appInstall);
        $this->assertStringContainsString("'pwaInstallTitle' => \$footerSettings['app_button_title'] ?? null", $appInstall);
        $this->assertStringContainsString("'pwaInstallSubtitle' => \$footerSettings['app_button_subtitle'] ?? null", $appInstall);

        $this->assertStringContainsString('$socialLinks', $social);

        $this->assertStringContainsString("\$footerSettings['legal_links']", $legal);
        $this->assertStringNotContainsString('Privacy Policy', $legal);
        $this->assertStringNotContainsString('Terms of Service', $legal);

        $this->assertStringContainsString("\$footerSettings['trust_badges']", $trust);
        $this->assertStringNotContainsString('Visa_Inc._logo.svg', $trust);
        $this->assertStringNotContainsString('Mastercard-logo.svg', $trust);
        $this->assertStringNotContainsString('PayPal.svg', $trust);
    }
}
