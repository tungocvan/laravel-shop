<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsitePwaRuntimeSyncConfigurationTest extends TestCase
{
    public function test_pwa_runtime_exposes_version_contract_and_checks_updates_on_foreground(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/WebsiteController.php'));
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $runtime = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));
        $worker = file_get_contents(base_path('public/service-worker.js'));

        $this->assertStringContainsString('function pwaVersion(', $controller);
        $this->assertStringContainsString("'version' => substr(hash('sha256'", $controller);
        $this->assertStringContainsString('/website-pwa-version.json', $routes);
        $this->assertStringContainsString("name('website.pwa.version')", $routes);

        $this->assertStringContainsString("const versionUrl = '/website-pwa-version.json'", $runtime);
        $this->assertStringContainsString("document.addEventListener('visibilitychange'", $runtime);
        $this->assertStringContainsString("window.addEventListener('focus'", $runtime);
        $this->assertStringContainsString('Website vừa được cập nhật.', $runtime);
        $this->assertStringContainsString('Cập nhật ngay', $runtime);
        $this->assertStringContainsString("registration.update()", $runtime);

        $this->assertStringContainsString("const VERSION_URL = '/website-pwa-version.json'", $worker);
        $this->assertStringContainsString("type === 'REFRESH_PWA_ASSETS'", $worker);
        $this->assertStringContainsString("type === 'SKIP_WAITING'", $worker);
        $this->assertStringContainsString("cache: 'no-store'", $worker);
    }
}
