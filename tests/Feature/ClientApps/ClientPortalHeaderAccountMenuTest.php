<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Route;
use Modules\ClientPortal\Services\PortalAccountPresenter;
use Tests\TestCase;

class ClientPortalHeaderAccountMenuTest extends TestCase
{
    use RefreshDatabase;

    public function test_account_and_settings_routes_require_the_client_web_guard(): void
    {
        foreach (['client.apps.account', 'client.apps.settings'] as $name) {
            $route = Route::getRoutes()->getByName($name);

            $this->assertNotNull($route);
            $this->assertContains('auth:web', $route->gatherMiddleware());

            $this->get(route($name))
                ->assertRedirect(route('client.apps.login'));
        }
    }

    public function test_launcher_and_application_shell_share_the_clientportal_account_menu(): void
    {
        $launcher = file_get_contents(base_path('Modules/ClientPortal/resources/views/pages/apps.blade.php'));
        $application = file_get_contents(base_path('Modules/ClientPortal/resources/views/layouts/application.blade.php'));
        $menu = file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/account-menu.blade.php'));

        $this->assertStringContainsString("ClientPortal::partials.account-menu", $launcher);
        $this->assertStringContainsString("ClientPortal::partials.account-menu", $application);
        $this->assertStringContainsString('data-client-account-menu', $menu);
        $this->assertStringContainsString('<details', $menu);
        $this->assertStringContainsString('<summary', $menu);
        $this->assertStringContainsString("event.key !== 'Escape'", $menu);
        $this->assertStringNotContainsString('x-data=', $menu);
        $this->assertStringNotContainsString('x-show=', $menu);
    }

    public function test_authenticated_user_sees_safe_account_information_and_post_logout_action(): void
    {
        $user = $this->createUser([
            'name' => 'Nguyễn Văn An',
            'email' => 'an@example.com',
            'phone' => '0909000000',
            'google_id' => 'private-google-id',
            'google_token' => 'private-google-token',
            'email_verified_at' => now(),
        ]);

        $response = $this->actingAs($user, 'web')
            ->get(route('client.apps.account'))
            ->assertOk()
            ->assertSee('Nguyễn Văn An')
            ->assertSee('an@example.com')
            ->assertSee('0909000000')
            ->assertSee('Đã xác minh')
            ->assertSee('Đã liên kết');

        $response->assertDontSee('private-google-id');
        $response->assertDontSee('private-google-token');
        $response->assertSee('method="POST"', false);
        $response->assertSee('action="'.route('logout').'"', false);
    }

    public function test_settings_exposes_existing_google_link_only_for_unlinked_accounts(): void
    {
        $unlinked = $this->createUser([
            'name' => 'Unlinked User',
            'email' => 'unlinked@example.com',
        ]);

        $this->actingAs($unlinked, 'web')
            ->get(route('client.apps.settings'))
            ->assertOk()
            ->assertSee('Liên kết Google')
            ->assertSee(route('client.apps.google.link'), false);

        $linked = $this->createUser([
            'name' => 'Linked User',
            'email' => 'linked@example.com',
            'google_id' => 'linked-google-id',
        ]);

        $this->actingAs($linked, 'web')
            ->get(route('client.apps.settings'))
            ->assertOk()
            ->assertSee('Đã liên kết')
            ->assertDontSee('>Liên kết Google<', false);
    }

    public function test_presenter_only_returns_allowlisted_account_state_and_local_initials(): void
    {
        $user = $this->createUser([
            'name' => 'Nguyễn Văn An',
            'email' => 'presenter@example.com',
            'google_id' => 'provider-id',
            'google_token' => 'provider-token',
        ]);

        $account = app(PortalAccountPresenter::class)->for($user);

        $this->assertSame(
            ['name', 'email', 'phone', 'initials', 'avatar_url', 'email_verified', 'google_linked'],
            array_keys($account)
        );
        $this->assertSame('NA', $account['initials']);
        $this->assertTrue($account['google_linked']);
        $this->assertNotContains('provider-id', $account, true);
        $this->assertNotContains('provider-token', $account, true);
    }

    public function test_account_menu_core_is_application_neutral(): void
    {
        $source = strtolower(
            file_get_contents(base_path('Modules/ClientPortal/resources/views/partials/account-menu.blade.php')).
            file_get_contents(base_path('Modules/ClientPortal/Http/Controllers/AccountController.php')).
            file_get_contents(base_path('Modules/ClientPortal/Services/PortalAccountPresenter.php'))
        );

        $this->assertStringNotContainsString('muasamcong', $source);
        $this->assertStringNotContainsString('client.request', $source);
        $this->assertStringNotContainsString('hasrole(', $source);
        $this->assertStringNotContainsString('hasanyrole(', $source);
    }

    private function createUser(array $attributes): User
    {
        return User::query()->create(array_merge([
            'name' => 'Portal User',
            'email' => 'portal-'.uniqid().'@example.com',
            'password' => Hash::make('StrongPassword123!'),
            'is_active' => true,
        ], $attributes));
    }
}
