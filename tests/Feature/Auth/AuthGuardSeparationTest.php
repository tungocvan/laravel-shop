<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Tests\TestCase;

class AuthGuardSeparationTest extends TestCase
{
    public function test_guest_client_apps_redirects_to_client_login(): void
    {
        $this->get('/my-apps')
            ->assertRedirect(route('login'));
    }

    public function test_client_session_can_access_client_apps(): void
    {
        $user = new User();
        $user->id = 1001;

        $this->actingAs($user, 'web')
            ->get('/my-apps')
            ->assertOk()
            ->assertSee('Ứng dụng của tôi');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    }

    public function test_client_login_page_uses_web_portal_and_admin_login_uses_admin_portal(): void
    {
        $this->get('/login')
            ->assertOk()
            ->assertViewHas('guard', 'web');

        $this->get('/admin/login')
            ->assertOk()
            ->assertViewHas('guard', 'admin');
    }

    public function test_client_logout_does_not_logout_admin_guard(): void
    {
        $user = new User();
        $user->id = 1002;

        $this->actingAs($user, 'web');
        $this->actingAs($user, 'admin');

        $this->post('/logout')
            ->assertRedirect(route('login'));

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_admin_logout_does_not_logout_client_guard(): void
    {
        $user = new User();
        $user->id = 1003;

        $this->actingAs($user, 'web');
        $this->actingAs($user, 'admin');

        $this->post('/admin/logout')
            ->assertRedirect(route('admin.login'));

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    }
}
