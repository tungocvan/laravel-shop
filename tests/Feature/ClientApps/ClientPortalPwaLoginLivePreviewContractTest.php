<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Tests\TestCase;

class ClientPortalPwaLoginLivePreviewContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_editor_is_first_and_preview_tracks_editable_fields(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/admin/pwa-settings.blade.php'));

        $this->assertLessThan(strpos($view, 'Thông tin PWA chung'), strpos($view, 'id="pwa-login-editor"'));
        $this->assertStringContainsString('x-model="badge"', $view);
        $this->assertStringContainsString('x-model="heading"', $view);
        $this->assertStringContainsString('x-model="description"', $view);
        $this->assertStringContainsString('x-model="showIntro"', $view);
        $this->assertStringContainsString('x-model="cards[{{ $index }}].title"', $view);
        $this->assertStringContainsString('x-model="cards[{{ $index }}].description"', $view);
        $this->assertStringContainsString('x-text="heading"', $view);
        $this->assertStringContainsString('x-text="description"', $view);
        $this->assertStringContainsString('x-show="showIntro"', $view);
        $this->assertStringContainsString('x-show="!showIntro"', $view);
        $this->assertStringContainsString('admin.client-apps.pwa.login.reset', $view);
    }

    public function test_reset_route_keeps_existing_guard_and_permissions(): void
    {
        $route = Route::getRoutes()->getByName('admin.client-apps.pwa.login.reset');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertContains('permission:edit_role,admin', $route->gatherMiddleware());
    }

    public function test_reset_defaults_do_not_overwrite_logo_path(): void
    {
        $settings = app(ClientPortalSettingsService::class);
        $settings->updatePwaLogin([
            'logo_path' => 'clientportal/login-logos/preserved.png',
            'heading' => 'Custom heading',
            'feature_cards' => [['enabled' => true, 'title' => 'Custom', 'description' => 'Custom']],
        ]);

        $defaults = config('clientportal.pwa.login', []);
        unset($defaults['logo_path']);
        $settings->updatePwaLogin($defaults);

        $login = $settings->pwaLogin();
        $this->assertSame('clientportal/login-logos/preserved.png', $login['logo_path']);
        $this->assertSame(config('clientportal.pwa.login.heading'), $login['heading']);
        $this->assertSame(config('clientportal.pwa.login.feature_cards'), $login['feature_cards']);
    }
}
