<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalPwaWebsiteAuthHandoffTest extends TestCase
{
    public function test_desktop_and_mobile_website_auth_links_expose_client_portal_pwa_targets(): void
    {
        $desktop = file_get_contents(base_path('Modules/Website/resources/views/components/header/account.blade.php'));
        $mobile = file_get_contents(base_path('Modules/Website/resources/views/components/header/mobile-menu.blade.php'));

        foreach ([$desktop, $mobile] as $content) {
            $this->assertStringContainsString("route('login')", $content);
            $this->assertStringContainsString("route('register')", $content);
            $this->assertStringContainsString("data-pwa-auth-target=\"{{ route('client.apps.login') }}\"", $content);
            $this->assertStringContainsString("data-pwa-auth-target=\"{{ route('client.apps.register') }}\"", $content);
        }
    }

    public function test_runtime_only_rewrites_auth_links_in_standalone_display_mode(): void
    {
        $runtime = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));

        $this->assertStringContainsString("window.matchMedia('(display-mode: standalone)').matches", $runtime);
        $this->assertStringContainsString('window.navigator.standalone === true', $runtime);
        $this->assertStringContainsString("if (!standalone) return", $runtime);
        $this->assertStringContainsString("document.querySelectorAll('[data-pwa-auth-target]')", $runtime);
        $this->assertStringContainsString("link.setAttribute('href', target)", $runtime);
    }
}
