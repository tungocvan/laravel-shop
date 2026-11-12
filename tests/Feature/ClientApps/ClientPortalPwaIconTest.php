<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\ClientPortal\Services\ClientPortalSettingsService;
use Modules\ClientPortal\Services\PwaIconService;
use Tests\TestCase;

class ClientPortalPwaIconTest extends TestCase
{
    use RefreshDatabase;

    public function test_client_manifest_and_apple_metadata_are_isolated_from_website(): void
    {
        $this->get('/my-apps/manifest.webmanifest')->assertOk()
            ->assertJsonPath('start_url', '/my-apps')
            ->assertJsonPath('scope', '/')
            ->assertJsonPath('icons.0.src', '/pwa/icon.svg');

        foreach (['pages/apps.blade.php', 'pages/login.blade.php', 'pages/register.blade.php', 'layouts/application.blade.php'] as $path) {
            $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/'.$path));
            $this->assertStringContainsString("route('client.apps.manifest')", $blade);
            $this->assertStringContainsString('rel="apple-touch-icon"', $blade);
        }
    }

    public function test_png_icon_is_resized_and_exposed_in_manifest(): void
    {
        if (!extension_loaded('gd')) {
            $this->markTestSkipped('PHP GD required for icon resizing');
        }

        Storage::fake('public');
        $image = imagecreatetruecolor(512, 512);
        ob_start();
        imagepng($image);
        $png = ob_get_clean();
        imagedestroy($image);

        $file = UploadedFile::fake()->createWithContent('icon.png', $png);
        $version = app(PwaIconService::class)->store($file);
        app(ClientPortalSettingsService::class)->updatePwaGeneral(['icon_version' => $version]);

        foreach (['180', '192', '512', 'maskable'] as $size) {
            Storage::disk('public')->assertExists("clientportal/pwa-icons/{$version}-{$size}.png");
        }
        $this->get('/my-apps/manifest.webmanifest')->assertOk()
            ->assertJsonPath('icons.0.sizes', '192x192')
            ->assertJsonPath('icons.2.purpose', 'maskable');
        $this->assertStringContainsString($version, app(PwaIconService::class)->appleTouchIcon($version));
    }

    public function test_invalid_icon_version_falls_back_to_default(): void
    {
        Storage::fake('public');
        app(ClientPortalSettingsService::class)->updatePwaGeneral(['icon_version' => '../../unsafe']);
        $this->assertSame('/pwa/icon.svg', app(PwaIconService::class)->appleTouchIcon(app(ClientPortalSettingsService::class)->pwaGeneral()['icon_version']));
        app(ClientPortalSettingsService::class)->updatePwaGeneral(['icon_version' => null]);
        $this->get('/my-apps/manifest.webmanifest')->assertJsonPath('icons.0.src', '/pwa/icon.svg');
    }
}
