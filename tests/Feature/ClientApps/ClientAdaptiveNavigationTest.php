<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class ClientAdaptiveNavigationTest extends TestCase
{
    public function test_application_shell_delegates_to_shared_adaptive_navigation(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));

        $this->assertStringContainsString('PortalNavigationResolver', $layout);
        $this->assertStringContainsString("where('placement', 'primary')", $layout);
        $this->assertStringContainsString("where('placement', 'more')", $layout);
        $this->assertStringContainsString('ClientPortal::partials.adaptive-navigation', $layout);
        $this->assertStringNotContainsString('partials.mobile-nav', $layout);
    }

    public function test_shared_navigation_uses_one_contract_for_mobile_tablet_and_desktop(): void
    {
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));

        $this->assertStringContainsString('sm:hidden', $navigation);
        $this->assertStringContainsString('hidden sm:flex', $navigation);
        $this->assertStringContainsString('lg:w-64', $navigation);
        $this->assertStringContainsString('$primaryNavigation', $navigation);
        $this->assertStringContainsString('$moreNavigation', $navigation);
        $this->assertStringContainsString('aria-current="page"', $navigation);
    }

    public function test_shared_navigation_is_application_neutral_and_uses_manifest_icons(): void
    {
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));
        $normalizedNavigation = strtolower($navigation);

        $this->assertStringContainsString("'name' => $item['icon']", $navigation);
        $this->assertStringContainsString('ClientPortal::partials.navigation-icon', $navigation);
        $this->assertStringNotContainsString('client.muasamcong.', $normalizedNavigation);
        $this->assertStringNotContainsString('applications.muasamcong', $normalizedNavigation);
        $this->assertStringNotContainsString('client.request.', $normalizedNavigation);
        $this->assertStringNotContainsString('applications.request', $normalizedNavigation);
        $this->assertStringNotContainsString('hasRole', $navigation);
    }

    public function test_shared_navigation_blade_compiles_without_custom_component_registration(): void
    {
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));

        $compiled = app('blade.compiler')->compileString($navigation);

        $this->assertNotEmpty($compiled);
        $this->assertStringNotContainsString('<x-client-portal::navigation-icon', $navigation);
    }

    public function test_navigation_icon_partial_has_generic_fallback(): void
    {
        $icon = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/navigation-icon.blade.php'));

        $this->assertStringContainsString("'squares-2x2'", $icon);
        $this->assertStringContainsString('$paths[$name] ?? $paths[\'squares-2x2\']', $icon);
        $this->assertStringContainsString('aria-hidden="true"', $icon);
    }
}
