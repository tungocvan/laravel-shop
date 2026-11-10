<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Tests\TestCase;

class ClientPortalPwaLoginLogoContractTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_logo_is_optional_and_never_uses_site_logo_fallback(): void
    {
        Storage::fake('public');
        $settings = app(ClientPortalSettingsService::class);
        $this->assertNull($settings->pwaLogin()['logo_url']);

        $this->get('/my-apps/login')->assertOk()->assertSee('pwaLogoUrl', false);
        $blade = file_get_contents(base_path('Modules/Auth/resources/views/livewire/auth/login-form-pwa.blade.php'));
        $this->assertStringContainsString('@if(filled($logo))', $blade);
        $this->assertStringNotContainsString("asset('storage/logo.png')", $blade);
    }

    public function test_login_logo_path_is_resolved_only_for_existing_managed_images(): void
    {
        Storage::fake('public');
        $settings = app(ClientPortalSettingsService::class);
        $path = UploadedFile::fake()->image('brand.png')->store('clientportal/login-logos', 'public');
        $settings->updatePwaLogin(['logo_path' => $path]);
        $this->assertSame(Storage::disk('public')->url($path), $settings->pwaLogin()['logo_url']);

        Storage::disk('public')->delete($path);
        $this->assertNull($settings->pwaLogin()['logo_url']);
        $settings->updatePwaLogin(['logo_path' => '../secret.png']);
        $this->assertNull($settings->pwaLogin()['logo_url']);
    }

    public function test_logo_upload_route_requires_admin_authentication(): void
    {
        Storage::fake('public');
        $this->post(route('admin.client-apps.pwa.login.logo.update'), [
            'logo' => UploadedFile::fake()->image('brand.png'),
        ])->assertRedirect();
        $this->assertSame([], Storage::disk('public')->allFiles('clientportal/login-logos'));
    }
}
