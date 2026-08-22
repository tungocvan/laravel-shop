<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

class WebsiteFrontendLayoutDecompositionConfigurationTest extends TestCase
{
    public function test_frontend_layout_is_an_orchestration_shell(): void
    {
        $layout = file_get_contents(base_path('Modules/Website/resources/views/layouts/frontend.blade.php'));

        foreach ([
            "Website::partials.layout.head-meta",
            "Website::partials.layout.runtime-head",
            "Website::partials.header",
            "Website::partials.footer",
            "Website::partials.layout.global-toast",
            "Website::partials.layout.runtime-scripts",
        ] as $partial) {
            $this->assertStringContainsString($partial, $layout);
        }

        $this->assertStringContainsString('id="main-content"', $layout);
        $this->assertStringContainsString('@isset($slot)', $layout);
        $this->assertStringNotContainsString('navigator.serviceWorker.register', $layout);
        $this->assertStringNotContainsString('@notify.window', $layout);
        $this->assertStringNotContainsString('window.CHAT_CONFIG_HOST', $layout);
    }

    public function test_runtime_contracts_are_preserved_in_dedicated_partials(): void
    {
        $headMeta = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/head-meta.blade.php'));
        $runtimeHead = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php'));
        $toast = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/global-toast.blade.php'));
        $runtimeScripts = file_get_contents(base_path('Modules/Website/resources/views/partials/layout/runtime-scripts.blade.php'));

        $this->assertStringContainsString('meta charset="utf-8"', $headMeta);
        $this->assertStringContainsString('name="viewport"', $headMeta);
        $this->assertStringContainsString('$websiteSeo', $headMeta);
        $this->assertStringContainsString('$siteFavicon', $headMeta);

        $this->assertStringContainsString('{!! $headerScript !!}', $runtimeHead);
        $this->assertStringContainsString('{!! $analyticsCode ?? \'\' !!}', $runtimeHead);
        $this->assertStringContainsString('<x-realtime-config />', $runtimeHead);
        $this->assertStringContainsString("Website::partials.design-tokens", $runtimeHead);
        $this->assertStringContainsString('@livewireStyles', $runtimeHead);

        $this->assertStringContainsString('@notify.window', $toast);
        $this->assertStringContainsString('@alert.window', $toast);
        $this->assertStringContainsString("setTimeout(() => open = false, 4000)", $toast);

        $this->assertStringContainsString('@livewireScripts', $runtimeScripts);
        $this->assertStringContainsString("navigator.serviceWorker.register('/service-worker.js')", $runtimeScripts);
    }
}
