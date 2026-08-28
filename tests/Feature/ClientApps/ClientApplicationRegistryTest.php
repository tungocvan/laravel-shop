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

    public function test_registry_exposes_open_portal_ui_contract_without_requiring_manifest_migration(): void
    {
        $application = app(ApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);
        $this->assertSame(['mode' => 'standard'], $application['layout']);
        $this->assertSame([], $application['capabilities']);
        $this->assertSame([], $application['quick_actions']);
        $this->assertNotEmpty($application['navigation']);
        $this->assertSame('drug-pricing', $application['navigation'][0]['key']);
        $this->assertSame('client.muasamcong.drug-pricing', $application['navigation'][0]['route']);
        $this->assertSame('client.muasamcong.drug-pricing.view', $application['navigation'][0]['permission']);
        $this->assertSame('primary', $application['navigation'][0]['placement']);
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
