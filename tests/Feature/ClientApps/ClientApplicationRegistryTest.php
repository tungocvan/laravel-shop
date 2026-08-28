<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Modules\ClientPortal\Services\ApplicationRegistry;
use Modules\ClientPortal\Services\PortalContextResolver;
use Tests\TestCase;

class ClientApplicationRegistryTest extends TestCase
{
    public function test_registry_discovers_muasamcong_adapter_manifest(): void
    {
        $application = app(ApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);
        $this->assertSame('Mua sắm công', $application['name']);
        $this->assertSame('Muasamcong', $application['module']);
        $this->assertSame('client.muasamcong.dashboard', $application['route']);
        $this->assertSame('client.muasamcong.access', $application['permission']);
        $this->assertSame('drug-pricing', $application['features'][0]['key']);
    }

    public function test_registry_exposes_explicit_open_portal_ui_contract(): void
    {
        $application = app(ApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);
        $this->assertSame(['mode' => 'workspace'], $application['layout']);
        $this->assertContains('search', $application['capabilities']);
        $this->assertContains('background-jobs', $application['capabilities']);
        $this->assertSame('drug-search', $application['quick_actions'][0]['key']);
        $this->assertSame('overview', $application['navigation'][0]['key']);
        $this->assertSame('client.muasamcong.dashboard', $application['navigation'][0]['route']);
        $this->assertSame('primary', $application['navigation'][0]['placement']);
        $this->assertSame('more', collect($application['navigation'])->firstWhere('key', 'wishlist')['placement']);
    }

    public function test_request_uses_same_manifest_contract_without_portal_core_special_case(): void
    {
        $application = app(ApplicationRegistry::class)->find('request');

        $this->assertNotNull($application);
        $this->assertSame('Request', $application['module']);
        $this->assertSame(['mode' => 'workspace'], $application['layout']);
        $this->assertContains('background-jobs', $application['capabilities']);
        $this->assertSame('create', $application['quick_actions'][0]['key']);
        $this->assertSame('overview', $application['navigation'][0]['key']);
        $this->assertSame('more', collect($application['navigation'])->firstWhere('key', 'processed')['placement']);

        $create = collect($application['navigation'])->firstWhere('key', 'create');
        $mine = collect($application['navigation'])->firstWhere('key', 'mine');
        $inbox = collect($application['navigation'])->firstWhere('key', 'inbox');

        $this->assertSame(
            ['client.request.create.view', 'request.instance.create'],
            $create['permissions']
        );
        $this->assertSame(
            ['client.request.mine.view', 'request.instance.view-own'],
            $mine['permissions']
        );
        $this->assertSame(
            ['client.request.inbox.view', 'request.task.view'],
            $inbox['permissions']
        );
    }

    public function test_application_shell_consumes_resolved_navigation_instead_of_app_specific_mobile_partial(): void
    {
        $layout = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));
        $resolver = file_get_contents(base_path('Modules/ClientPortal/Services/PortalNavigationResolver.php'));

        $this->assertStringContainsString('PortalNavigationResolver', $layout);
        $this->assertStringContainsString("where('placement', 'primary')", $layout);
        $this->assertStringContainsString("where('placement', 'more')", $layout);
        $this->assertStringNotContainsString("partials.mobile-nav", $layout);
        $this->assertStringContainsString("permissions->every", $resolver);
    }

    public function test_portal_context_has_stable_shape_when_user_has_no_available_applications(): void
    {
        $user = new User();
        $user->id = 2003;

        $context = app(PortalContextResolver::class)->resolve($user);

        $this->assertSame(2003, $context['user_id']);
        $this->assertSame(0, $context['application_count']);
        $this->assertFalse($context['has_access']);
        $this->assertFalse($context['requires_application_selection']);
        $this->assertNull($context['single_application']);
        $this->assertTrue($context['applications']->isEmpty());
    }

    public function test_guest_is_redirected_from_muasamcong_client_dashboard(): void
    {
        $this->get('/apps/muasamcong')->assertRedirect(route('client.apps.login'));
    }

    public function test_guest_launcher_is_redirected_to_dedicated_pwa_login(): void
    {
        $this->get('/my-apps')->assertRedirect(route('client.apps.login'));
    }

    public function test_authenticated_user_without_application_permission_is_forbidden(): void
    {
        $user = new User();
        $user->id = 2001;
        $this->actingAs($user, 'web')->get('/apps/muasamcong')->assertForbidden();
    }

    public function test_launcher_stays_available_when_client_permissions_are_not_synced_yet(): void
    {
        $user = new User();
        $user->id = 2002;
        $this->actingAs($user, 'web')->get('/my-apps')->assertOk()->assertSee('Ứng dụng của tôi')->assertSee('Chưa có ứng dụng được cấp');
    }
}
