<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientPortalDesktopLayoutContractTest extends TestCase
{
    public function test_application_shell_uses_shared_wide_desktop_container_contract(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));

        $this->assertSame(2, substr_count($layout, 'max-w-[1536px]'));
        $this->assertStringContainsString('xl:px-10 2xl:px-12', $layout);
        $this->assertStringContainsString("route('website.manifest')", $layout);
        $this->assertStringNotContainsString('/manifest.webmanifest', $layout);
    }

    public function test_adaptive_navigation_keeps_compact_tablet_and_balanced_desktop_widths(): void
    {
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));

        $this->assertStringContainsString('sm:w-20', $navigation);
        $this->assertStringContainsString('lg:w-56', $navigation);
        $this->assertStringContainsString('xl:w-60', $navigation);
        $this->assertStringNotContainsString('lg:w-64', $navigation);
    }
}
