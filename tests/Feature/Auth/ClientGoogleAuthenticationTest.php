<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\Auth\Services\GoogleWebAuthService;
use Tests\TestCase;

class ClientGoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_google_user_can_create_active_web_account_without_storing_tokens(): void
    {
        $user = app(GoogleWebAuthService::class)->resolve(
            $this->googleUser('google-100', 'new@example.com', true),
        );

        $this->assertTrue($user->is_active);
        $this->assertNotNull($user->email_verified_at);
        $this->assertSame('google-100', $user->google_id);
        $this->assertNull($user->google_token);
        $this->assertNull($user->google_refresh_token);
        $this->assertNotEmpty($user->password);
    }

    public function test_existing_linked_google_id_is_reused_without_matching_by_email(): void
    {
        $linked = User::query()->create([
            'name' => 'Linked User',
            'email' => 'old@example.com',
            'password' => Hash::make('Password123!'),
            'google_id' => 'google-200',
            'is_active' => true,
        ]);

        $resolved = app(GoogleWebAuthService::class)->resolve(
            $this->googleUser('google-200', 'changed@example.com', true),
        );

        $this->assertTrue($resolved->is($linked));
        $this->assertSame('old@example.com', $resolved->email);
    }

    public function test_matching_existing_email_is_not_auto_linked_to_google(): void
    {
        User::query()->create([
            'name' => 'Password User',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        try {
            app(GoogleWebAuthService::class)->resolve(
                $this->googleUser('google-300', 'existing@example.com', true),
            );
            $this->fail('Expected unsafe email-only linking to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        $this->assertNull(User::query()->where('email', 'existing@example.com')->sole()->google_id);
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(GoogleWebAuthService::class)->resolve(
            $this->googleUser('google-400', 'unverified@example.com', false),
        );
    }

    public function test_google_callback_logs_into_web_guard_and_redirects_to_client_apps(): void
    {
        config([
            'services.google.client_id' => 'test-client-id',
            'services.google.client_secret' => 'test-client-secret',
        ]);

        $googleUser = $this->googleUser('google-500', 'callback@example.com', true);
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('client.apps.google.callback'))
            ->assertRedirect(route('client.apps.index'));

        $user = User::query()->where('google_id', 'google-500')->sole();
        $this->assertAuthenticatedAs($user, 'web');
        $this->assertGuest('admin');
        $this->assertNotNull($user->last_login_at);
    }

    private function googleUser(string $id, string $email, bool $verified): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw([
                'sub' => $id,
                'email' => $email,
                'email_verified' => $verified,
                'name' => 'Google User',
                'picture' => 'https://example.com/avatar.png',
            ])
            ->map([
                'id' => $id,
                'name' => 'Google User',
                'email' => $email,
                'avatar' => 'https://example.com/avatar.png',
            ]);
    }
}
