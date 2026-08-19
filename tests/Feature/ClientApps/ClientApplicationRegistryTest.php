<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use App\Services\ClientApplicationRegistry;
use Tests\TestCase;

class ClientApplicationRegistryTest extends TestCase
{
    public function test_registry_discovers_muasamcong_manifest(): void
    {
        $application = app(ClientApplicationRegistry::class)->find('muasamcong');

        $this->assertNotNull($application);
        $this->assertSame('Mua sắm công', $application['name']);
        $this->assertSame('client.muasamcong.dashboard', $application['route']);
        $this->assertSame('client.muasamcong.access', $application['permission']);
        $this->assertSame('drug-pricing', $application['features'][0]['key']);
    }

    public function test_guest_is_redirected_from_muasamcong_client_dashboard(): void
    {
        $this->get('/apps/muasamcong')
            ->assertRedirect(route('login'));
    }

    public function test_authenticated_user_without_application_permission_is_forbidden(): void
    {
        $user = new User();
        $user->id = 2001;

        $this->actingAs($user, 'web')
            ->get('/apps/muasamcong')
            ->assertForbidden();
    }

    public function test_launcher_stays_available_when_client_permissions_are_not_synced_yet(): void
    {
        $user = new User();
        $user->id = 2002;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('Ứng dụng của tôi')
            ->assertSee('Chưa có ứng dụng được cấp');
    }
}
