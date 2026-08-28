<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Tests\TestCase;

class ClientPortalPwaInstallUxTest extends TestCase
{
    public function test_launcher_uses_dedicated_adaptive_install_partial(): void
    {
        $launcher = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/apps.blade.php'));
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString("ClientPortal::partials.pwa-install", $launcher);
        $this->assertStringContainsString('data-pwa-install-button', $installer);
        $this->assertStringContainsString('data-pwa-install-modal', $installer);
        $this->assertStringContainsString('beforeinstallprompt', $installer);
        $this->assertStringContainsString('appinstalled', $installer);
    }

    public function test_installer_detects_ios_ipados_and_standalone_mode(): void
    {
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString('/iPad|iPhone|iPod/', $installer);
        $this->assertStringContainsString("navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1", $installer);
        $this->assertStringContainsString("window.matchMedia('(display-mode: standalone)')", $installer);
        $this->assertStringContainsString('window.navigator.standalone === true', $installer);
        $this->assertStringContainsString('button.hidden = true', $installer);
    }

    public function test_installer_preserves_native_chromium_prompt_contract(): void
    {
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString('event.preventDefault()', $installer);
        $this->assertStringContainsString('deferredPrompt = event', $installer);
        $this->assertStringContainsString('await prompt.prompt()', $installer);
        $this->assertStringContainsString('await prompt.userChoice.catch(() => null)', $installer);
    }

    public function test_installer_exposes_safari_add_to_home_screen_guidance(): void
    {
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString('data-pwa-ios-guide', $installer);
        $this->assertStringContainsString('data-pwa-ios-browser-guide', $installer);
        $this->assertStringContainsString('Nhấn Chia sẻ', $installer);
        $this->assertStringContainsString('Thêm vào Màn hình chính', $installer);
        $this->assertStringContainsString("if (isIOS && isSafari) return showModal('ios')", $installer);
        $this->assertStringContainsString("if (isIOS) return showModal('ios-browser')", $installer);
    }

    public function test_non_ios_browser_does_not_show_fake_install_cta_without_native_prompt(): void
    {
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString('button.hidden = !(isIOS || deferredPrompt)', $installer);
        $this->assertStringNotContainsString("showModal('generic')", $installer);
    }

    public function test_launcher_install_copy_is_config_driven_with_backward_compatible_fallbacks(): void
    {
        $installer = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/pwa-install.blade.php'));

        $this->assertStringContainsString("\$launcher['install_button_text'] ?? 'Cài ứng dụng'", $installer);
        $this->assertStringContainsString("\$launcher['install_ios_heading'] ?? 'Cài ứng dụng trên iPhone/iPad'", $installer);
        $this->assertStringContainsString("\$launcher['install_ios_description'] ??", $installer);
        $this->assertStringContainsString("\$launcher['install_ios_browser_heading'] ?? 'Hãy mở trang này bằng Safari'", $installer);
        $this->assertStringContainsString("\$launcher['install_ios_browser_description'] ??", $installer);
        $this->assertStringContainsString("\$launcher['install_close_text'] ?? 'Đã hiểu'", $installer);
    }

    public function test_install_partial_renders_when_launcher_only_has_legacy_install_button_key(): void
    {
        $html = view('ClientPortal::partials.pwa-install', [
            'launcher' => ['install_button_text' => 'Cài đặt'],
        ])->render();

        $this->assertStringContainsString('Cài đặt', $html);
        $this->assertStringContainsString('Cài ứng dụng trên iPhone/iPad', $html);
        $this->assertStringContainsString('Hãy mở trang này bằng Safari', $html);
        $this->assertStringContainsString('Đã hiểu', $html);
    }

    public function test_authenticated_launcher_renders_install_ux_contract(): void
    {
        $user = new User();
        $user->id = 6106;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('data-pwa-install-button', false)
            ->assertSee('data-pwa-install-modal', false)
            ->assertSee('beforeinstallprompt', false)
            ->assertSee('Thêm vào Màn hình chính');
    }

    public function test_client_portal_pages_reuse_the_root_website_pwa_manifest(): void
    {
        $pages = [
            'login.blade.php',
            'register.blade.php',
            'verify-email.blade.php',
            'apps.blade.php',
        ];

        foreach ($pages as $page) {
            $content = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/'.$page));

            $this->assertStringContainsString("route('website.manifest')", $content, $page);
            $this->assertStringNotContainsString('/manifest.webmanifest', $content, $page);
        }

        $manifest = $this->get('/website-manifest.webmanifest')
            ->assertOk()
            ->json();

        $this->assertSame('/', $manifest['id']);
        $this->assertSame('/', $manifest['start_url']);
        $this->assertSame('/', $manifest['scope']);
        $this->assertSame('standalone', $manifest['display']);
    }
}
