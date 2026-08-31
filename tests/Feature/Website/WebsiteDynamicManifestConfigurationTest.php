<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteDynamicManifestConfigurationTest extends TestCase
{
    public function test_storefront_uses_dynamic_manifest_endpoint_instead_of_static_client_portal_manifest(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/WebsiteController.php'));
        $head = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));
        $staticManifest = file_get_contents(base_path('public/manifest.webmanifest'));

        $this->assertStringContainsString("/website-manifest.webmanifest", $routes);
        $this->assertStringContainsString("name('website.manifest')", $routes);
        $this->assertStringContainsString("get('website.appearance')", $controller);
        $this->assertStringContainsString("'name' => \$appearance['application_name']", $controller);
        $this->assertStringContainsString("'short_name' => \$appearance['apple_title']", $controller);
        $this->assertStringContainsString("'theme_color' => \$appearance['theme_color']", $controller);
        $this->assertStringContainsString("'background_color' => \$appearance['background_color']", $controller);
        $this->assertStringContainsString("'start_url' => '/my-apps'", $controller);
        $this->assertStringContainsString("route('website.manifest')", $head);
        $this->assertStringNotContainsString('href="/manifest.webmanifest"', $head);

        // The legacy Client Portal manifest remains independent and must not be mutated by Website Settings.
        $this->assertStringContainsString('INAFO Client Portal', $staticManifest);
        $this->assertStringContainsString('"start_url": "/my-apps"', $staticManifest);
    }
}
