<?php

namespace Tests\Feature\System;

use Tests\TestCase;

class RealtimeBrowserSocketConfigurationTest extends TestCase
{
    public function test_browser_socket_configuration_never_falls_back_to_internal_nodejs_host(): void
    {
        $socketClient = file_get_contents(base_path('resources/js/realtime/socket-client.js'));
        $authLayout = file_get_contents(base_path('Modules/Auth/resources/views/layouts/auth.blade.php'));
        $adminHead = file_get_contents(base_path('Modules/Admin/resources/views/layouts/partials/head.blade.php'));
        $websiteRuntimeHead = file_get_contents(
            base_path('Modules/Website/resources/views/partials/layout/runtime-head.blade.php')
        );

        $this->assertStringContainsString(
            'window.APP_CONFIG?.realtime?.url',
            $socketClient
        );
        $this->assertStringContainsString(
            'window.location.origin',
            $socketClient
        );

        $this->assertStringNotContainsString(
            'window.CHAT_CONFIG_HOST',
            $socketClient
        );

        $this->assertStringNotContainsString(
            '<x-realtime-config />',
            $authLayout,
            'Auth layout must not bootstrap realtime before login.'
        );

        foreach ([
            'admin head' => $adminHead,
            'website runtime head' => $websiteRuntimeHead,
        ] as $surface => $source) {
            $this->assertStringContainsString(
                '<x-realtime-config />',
                $source,
                "{$surface} must expose the canonical realtime browser config."
            );
        }

        foreach ([
            'auth layout' => $authLayout,
            'admin head' => $adminHead,
            'website runtime head' => $websiteRuntimeHead,
        ] as $surface => $source) {

            $this->assertStringNotContainsString(
                'window.CHAT_CONFIG_HOST',
                $source,
                "{$surface} must not expose the legacy socket host to the browser."
            );

            $this->assertStringNotContainsString(
                "config('realtime.host')",
                $source,
                "{$surface} must not expose the internal realtime host."
            );

            $this->assertStringNotContainsString(
                'NODEJS_SERVER_URL',
                $source,
                "{$surface} must not expose the internal Docker socket URL."
            );
        }
    }
}
