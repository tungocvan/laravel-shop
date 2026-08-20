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

        $this->assertStringContainsString("request.mode === 'navigate'", $serviceWorker);
        $this->assertStringContainsString('fetch(request).catch', $serviceWorker);
        $this->assertStringNotContainsString("cache.put(request", $serviceWorker);
    }

    public function test_dedicated_pwa_login_route_is_public_and_named(): void
    {
        $route = Route::getRoutes()->getByName('client.apps.login');

        $this->assertNotNull($route);
        $this->assertSame('my-apps/login', $route->uri());
        $this->assertNotContains('auth:web', $route->gatherMiddleware());
    }

    public function test_website_footer_uses_adaptive_pwa_installer(): void
    {
        $footer = file_get_contents(base_path('Modules/Website/resources/views/partials/footer.blade.php'));
        $installer = file_get_contents(base_path('Modules/Website/resources/views/partials/pwa-installer.blade.php'));

        $this->assertStringContainsString("Website::partials.pwa-installer", $footer);
        $this->assertStringContainsString('beforeinstallprompt', $installer);
        $this->assertStringContainsString('navigator.standalone', $installer);
        $this->assertStringContainsString('Thêm vào Màn hình chính', $installer);
    }

    public function test_client_launcher_exposes_pwa_metadata_for_authenticated_user(): void
    {
        $user = new User();
        $user->id = 3101;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('/manifest.webmanifest', false)
            ->assertSee('/service-worker.js', false)
            ->assertSee('Ứng dụng của tôi');
    }
}
