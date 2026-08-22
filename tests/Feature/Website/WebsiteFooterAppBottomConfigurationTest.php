<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFooterAppBottomConfigurationTest extends TestCase
{
    public function test_footer_info_exposes_app_install_and_bottom_content_management(): void
    {
        $component = file_get_contents(base_path('Modules/Website/Livewire/Admin/Footer/FooterInfo.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/livewire/admin/footer/footer-info.blade.php'));

        foreach ([
            "'footer.app_title'",
            "'footer.app_description'",
            "'footer.app_button_title'",
            "'footer.app_button_subtitle'",
            "'footer.appstore_url'",
            "'footer.playstore_url'",
            "'footer.copyright'",
            "'footer.legal_links'",
            "'footer.trust_badges'",
        ] as $setting) {
            $this->assertStringContainsString($setting, $component);
        }

        $this->assertStringContainsString('addLegalLink', $component);
        $this->assertStringContainsString('removeLegalLink', $component);
        $this->assertStringContainsString('addTrustBadge', $component);
        $this->assertStringContainsString('removeTrustBadge', $component);
        $this->assertStringContainsString("authorizeAdminPermission('website.footer.manage')", $component);

        $this->assertStringContainsString('Tải ứng dụng', $view);
        $this->assertStringContainsString('wire:model="app_title"', $view);
        $this->assertStringContainsString('wire:model="app_button_title"', $view);
        $this->assertStringContainsString('Footer Bottom', $view);
        $this->assertStringContainsString('wire:model="copyright"', $view);
        $this->assertStringContainsString('wire:click="addLegalLink"', $view);
        $this->assertStringContainsString('wire:click="addTrustBadge"', $view);
    }

    public function test_storefront_footer_reads_dynamic_app_legal_and_trust_content(): void
    {
        $provider = file_get_contents(base_path('Modules/Website/Providers/WebsiteServiceProvider.php'));
        $app = file_get_contents(base_path('Modules/Website/resources/views/components/footer/app-install.blade.php'));
        $pwa = file_get_contents(base_path('Modules/Website/resources/views/partials/pwa-installer.blade.php'));
        $legal = file_get_contents(base_path('Modules/Website/resources/views/components/footer/legal-links.blade.php'));
        $badges = file_get_contents(base_path('Modules/Website/resources/views/components/footer/trust-badges.blade.php'));

        $this->assertStringContainsString("'legal_links'=>\$settings->get('footer.legal_links'", $provider);
        $this->assertStringContainsString("'trust_badges'=>\$settings->get('footer.trust_badges'", $provider);
        $this->assertStringContainsString("\$footerSettings['app_title']", $app);
        $this->assertStringContainsString('pwaInstallTitle', $app);
        $this->assertStringContainsString('data-default-title', $pwa);
        $this->assertStringContainsString("\$footerSettings['legal_links']", $legal);
        $this->assertStringNotContainsString('Privacy Policy</a>', $legal);
        $this->assertStringContainsString("\$footerSettings['trust_badges']", $badges);
        $this->assertStringNotContainsString('Visa_Inc._logo.svg', $badges);
    }
}
