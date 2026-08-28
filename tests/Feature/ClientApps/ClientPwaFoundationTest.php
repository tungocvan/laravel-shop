<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class ClientPwaFoundationTest extends TestCase
{
    public function test_pwa_manifest_has_client_launcher_start_url(): void
    {
        $manifest = json_decode(file_get_contents(public_path('manifest.webmanifest')), true, flags: JSON_THROW_ON_ERROR);

        $this->assertSame('INAFO Client Portal', $manifest['name']);
        $this->assertSame('/my-apps', $manifest['start_url']);
        $this->assertSame('standalone', $manifest['display']);
        $this->assertNotEmpty($manifest['icons']);
    }

    public function test_service_worker_does_not_cache_authenticated_navigation_responses(): void
    {
        $serviceWorker = file_get_contents(public_path('service-worker.js'));
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));

        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString("fetch(request, { cache: 'no-store' }).catch", $serviceWorker);
        $this->assertStringNotContainsString("cache.put(request", $serviceWorker);
        $this->assertStringContainsString("updateViaCache: 'none'", $layout);
    }

    public function test_dedicated_pwa_login_route_is_public_and_named(): void
    {
        $route = Route::getRoutes()->getByName('client.apps.login');

        $this->assertNotNull($route);
        $this->assertSame('my-apps/login', $route->uri());
        $this->assertNotContains('auth:web', $route->gatherMiddleware());
    }

    public function test_client_logout_returns_to_dedicated_pwa_login(): void
    {
        $user = new User();
        $user->id = 3102;

        $this->actingAs($user, 'web')
            ->post('/logout')
            ->assertRedirect(route('client.apps.login'));
    }

    public function test_pwa_login_has_local_app_icon_fallback(): void
    {
        $loginForm = file_get_contents(base_path('Modules/Auth/Livewire/Auth/LoginForm.php'));

        $this->assertStringContainsString("asset('pwa/icon.svg')", $loginForm);
    }

    public function test_website_footer_uses_adaptive_pwa_installer(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $appInstall = file_get_contents(base_path('Modules/Website/resources/views/components/footer/app-install.blade.php'));
        $installer = file_get_contents(base_path('Modules/Website/resources/views/partials/pwa-installer.blade.php'));

        $this->assertStringContainsString('Website::components.footer.slot', $footer);
        $this->assertStringContainsString('Website::partials.pwa-installer', $appInstall);
        $this->assertStringContainsString('beforeinstallprompt', $installer);
        $this->assertStringContainsString('navigator.standalone', $installer);
        $this->assertStringContainsString('Thêm vào Màn hình chính', $installer);
    }

    public function test_price_list_workspace_polish_is_scoped_through_application_shell_extensions(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));
        $manifest = file_get_contents(base_path('Modules/ClientPortal/Applications/Muasamcong/manifest.php'));
        $polish = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/muasamcong/partials/price-list-workspace-polish.blade.php'));

        $this->assertStringContainsString('shell_extensions', $layout);
        $this->assertStringNotContainsString("routeIs('client.muasamcong.price-list*')", $layout);
        $this->assertStringNotContainsString('price-list-workspace-polish', $layout);
        $this->assertStringContainsString("'routes' => ['client.muasamcong.price-list*']", $manifest);
        $this->assertStringContainsString("'view' => 'ClientPortal::applications.muasamcong.partials.price-list-workspace-polish'", $manifest);
        $this->assertStringContainsString('.export-card', $polish);
        $this->assertStringContainsString('price-list-icon-action', $polish);
        $this->assertStringContainsString('data-action-icon', $polish);
        $this->assertStringContainsString('[data-email-open]', $polish);
        $this->assertStringContainsString('Đã gửi gần nhất', $polish);
        $this->assertStringContainsString('excel-file-ready', $polish);
        $this->assertStringContainsString('data.file_available === true', $polish);
        $this->assertStringContainsString('form[action*="/pdf"]', $polish);
        $this->assertStringContainsString('pdfConvert:', $polish);
        $this->assertStringContainsString("setIconAction(pdfConvert, 'pdf-convert', icons.pdfConvert, 'Tạo PDF')", $polish);
        $this->assertStringContainsString("setIconAction(pdfDownload, 'pdf', icons.pdf, 'Tải PDF')", $polish);
    }

    public function test_client_launcher_exposes_pwa_metadata_for_authenticated_user(): void
    {
        $user = new User();
        $user->id = 3101;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee(route('website.manifest'), false)
            ->assertSee('/service-worker.js', false)
            ->assertSee('Ứng dụng của tôi');
    }
}
