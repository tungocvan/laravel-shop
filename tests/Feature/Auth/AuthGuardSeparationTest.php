<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\View\View;
use Modules\Auth\Http\Controllers\AuthController;
use Tests\TestCase;

class AuthGuardSeparationTest extends TestCase
{
    public function test_guest_client_apps_redirects_to_client_login(): void
    {
        $this->get('/my-apps')
            ->assertRedirect(route('client.apps.login'));
    }

    public function test_client_session_can_access_client_apps(): void
    {
        $user = new User;
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
        /** @var AuthController $controller */
        $controller = app(AuthController::class);

        $clientView = $controller->clientLogin();
        $this->assertInstanceOf(View::class, $clientView);
        $this->assertSame('Auth::pages.auth.login', $clientView->name());
        $this->assertSame('web', $clientView->getData()['guard'] ?? null);

        $adminView = $controller->adminLogin();
        $this->assertInstanceOf(View::class, $adminView);
        $this->assertSame('Auth::pages.auth.login', $adminView->name());
        $this->assertSame('admin', $adminView->getData()['guard'] ?? null);
    }

    public function test_client_logout_does_not_logout_admin_guard_and_clears_site_data(): void
    {
        $user = new User;
        $user->id = 1002;

        $this->actingAs($user, 'web');
        $this->actingAs($user, 'admin');

        $this->post('/logout')
            ->assertRedirect(route('client.apps.login'))
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');

        $this->assertGuest('web');
        $this->assertAuthenticatedAs($user, 'admin');
    }

    public function test_admin_logout_does_not_logout_client_guard_and_clears_site_data(): void
    {
        $user = new User;
        $user->id = 1003;

        $this->actingAs($user, 'web');
        $this->actingAs($user, 'admin');

        $this->post('/admin/logout')
            ->assertRedirect(route('admin.login'))
            ->assertHeader('Clear-Site-Data', '"cache", "storage"');

        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
    }
}
