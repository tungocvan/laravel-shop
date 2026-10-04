<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
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
        $this->assertStringContainsString('sm:w-20', $navigation);
        $this->assertStringContainsString('lg:w-56', $navigation);
        $this->assertStringContainsString('xl:w-60', $navigation);
        $this->assertStringContainsString('$primaryNavigation', $navigation);
        $this->assertStringContainsString('$moreNavigation', $navigation);
        $this->assertStringContainsString('aria-current="page"', $navigation);
    }

    public function test_mobile_bottom_navigation_has_independent_visibility_and_order_contract(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));
        $resolver = file_get_contents(base_path('Modules/ClientPortal/Services/PortalNavigationResolver.php'));

        $this->assertStringContainsString("where('bottom_enabled', true)", $layout);
        $this->assertStringContainsString("sortBy('bottom_sort_order')", $layout);
        $this->assertStringContainsString('$mobilePrimaryNavigation', $navigation);
        $this->assertStringContainsString('$mobileMoreNavigation', $navigation);
        $this->assertStringContainsString("'bottom_enabled'", $resolver);
        $this->assertStringContainsString("'bottom_sort_order'", $resolver);
        $this->assertStringContainsString("'bottom_icon'", $resolver);
        $this->assertStringContainsString("pwaBottomNavigation", $layout);
        $this->assertStringContainsString("\$bottomNav['icon_size']", $navigation);
        $this->assertStringContainsString("\$bottomNav['text_font_size']", $navigation);
        $this->assertStringContainsString('background_opacity', $navigation);
        $this->assertStringContainsString("access->can", $resolver);
        $this->assertStringNotContainsString("['route'] =", $resolver);
        $this->assertStringNotContainsString("['permission'] =", $resolver);
    }

    public function test_shared_navigation_is_application_neutral_and_uses_manifest_icons(): void
    {
        $navigation = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/adaptive-navigation.blade.php'));
        $normalizedNavigation = strtolower($navigation);

        $this->assertStringContainsString("'name' => \$item['icon']", $navigation);
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

    public function test_shared_navigation_renders_mobile_appearance_without_undefined_variables(): void
    {
        $item = collect([[
            'name' => 'Tổng quan',
            'route' => 'client.apps.index',
            'icon' => 'home',
            'bottom_icon' => 'home',
        ]]);

        $request = Request::create(route('client.apps.index', absolute: false), 'GET');
        $matchedRoute = Route::getRoutes()->match($request);
        $request->setRouteResolver(fn () => $matchedRoute);
        app()->instance('request', $request);

        $html = view('ClientPortal::partials.adaptive-navigation', [
            'primaryNavigation' => $item,
            'moreNavigation' => collect(),
            'mobilePrimaryNavigation' => $item,
            'mobileMoreNavigation' => collect(),
            'bottomNavigationAppearance' => [
                'presentation_style' => 'neumorphism',
                'background_color' => '#ffffff',
                'background_opacity' => 95,
                'icon_color' => '#64748b',
                'text_color' => '#64748b',
                'active_icon_color' => '#1d4ed8',
                'active_text_color' => '#1e3a8a',
                'active_background_color' => '#eff6ff',
                'text_font_size' => 11,
                'icon_size' => 20,
                'min_height' => 72,
            ],
            'hideMobileNavigation' => false,
        ])->render();

        $this->assertStringContainsString('background-color:', $html);
        $this->assertStringContainsString('font-size: 11px', $html);
        $this->assertStringContainsString('width: 20px', $html);
        $this->assertStringContainsString('#64748b', $html);
        $this->assertStringContainsString('#eff6ff', $html);
        $this->assertStringContainsString('#1d4ed8', $html);
        $this->assertStringContainsString('#1e3a8a', $html);
        $this->assertStringContainsString('background-color: #f1f4f8', $html);
        $this->assertStringContainsString('border-radius: 22px', $html);
        $this->assertStringContainsString('box-shadow: 7px 7px 16px', $html);
        $this->assertStringContainsString('box-shadow: inset 4px 4px 8px', $html);
        $this->assertStringNotContainsString('box-shadow: 2px 2px 5px', $html);
        $this->assertStringContainsString('min-h-[2.25em]', $html);
    }

    public function test_navigation_icon_partial_has_generic_fallback(): void
    {
        $icon = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/navigation-icon.blade.php'));

        $this->assertStringContainsString("'squares-2x2'", $icon);
        $this->assertStringContainsString('$paths[$name] ?? $paths[\'squares-2x2\']', $icon);
        $this->assertStringContainsString('aria-hidden="true"', $icon);
    }
}
