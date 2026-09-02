<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Modules\Auth\Models\UserEmailVerification;
use Modules\Auth\Services\GoogleIdentityService;
use Tests\TestCase;

class ClientGoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_verified_google_user_can_create_active_web_account_without_storing_tokens(): void
    {
        $user = app(GoogleIdentityService::class)->resolve(
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

        $resolved = app(GoogleIdentityService::class)->resolve(
            $this->googleUser('google-200', 'changed@example.com', true),
        );

        $this->assertTrue($resolved->is($linked));
        $this->assertSame('old@example.com', $resolved->email);
    }

    public function test_recent_otp_verified_existing_email_is_auto_linked_to_google(): void
    {
        $verifiedAt = now();
        $existing = User::query()->create([
            'name' => 'OTP Verified Password User',
            'email' => 'existing@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $existing->forceFill(['email_verified_at' => $verifiedAt])->save();
        $this->createOtpProof($existing, $verifiedAt);

        $resolved = app(GoogleIdentityService::class)->resolve(
            $this->googleUser('google-300', 'existing@example.com', true),
        );

        $this->assertTrue($resolved->is($existing));
        $this->assertSame('google-300', $resolved->google_id);
        $this->assertNull($resolved->google_token);
        $this->assertNull($resolved->google_refresh_token);
    }

    public function test_legacy_verified_existing_email_is_not_auto_linked_without_otp_proof(): void
    {
        $existing = User::query()->create([
            'name' => 'Legacy Verified User',
            'email' => 'legacy@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);
        $existing->forceFill(['email_verified_at' => now()])->save();

        try {
            app(GoogleIdentityService::class)->resolve(
                $this->googleUser('google-302', 'legacy@example.com', true),
            );
            $this->fail('Expected legacy verified account auto-linking to be rejected without OTP provenance.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        $this->assertNull($existing->refresh()->google_id);
    }

    public function test_unverified_existing_email_is_not_auto_linked_to_google(): void
    {
        User::query()->create([
            'name' => 'Unverified Password User',
            'email' => 'pending@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        try {
            app(GoogleIdentityService::class)->resolve(
                $this->googleUser('google-301', 'pending@example.com', true),
            );
            $this->fail('Expected unverified local account auto-linking to be rejected.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('email', $e->errors());
        }

        $this->assertNull(User::query()->where('email', 'pending@example.com')->sole()->google_id);
    }

    public function test_google_id_conflict_with_different_email_owner_is_rejected(): void
    {
        User::query()->create([
            'name' => 'Google Owner',
            'email' => 'owner@example.com',
            'password' => Hash::make('Password123!'),
            'google_id' => 'google-conflict',
            'is_active' => true,
        ]);
        User::query()->create([
            'name' => 'Email Owner',
            'email' => 'incoming@example.com',
            'password' => Hash::make('Password123!'),
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(GoogleIdentityService::class)->resolve(
            $this->googleUser('google-conflict', 'incoming@example.com', true),
        );
    }

    public function test_unverified_google_email_is_rejected(): void
    {
        $this->expectException(ValidationException::class);

        app(GoogleIdentityService::class)->resolve(
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

    private function createOtpProof(User $user, $verifiedAt): void
    {
        UserEmailVerification::query()->create([
            'user_id' => $user->getKey(),
            'email' => mb_strtolower($user->email),
            'code_hash' => str_repeat('a', 64),
            'expires_at' => $verifiedAt->copy()->addMinutes(10),
            'last_sent_at' => $verifiedAt->copy()->subMinute(),
            'attempts' => 1,
            'verified_at' => $verifiedAt,
            'invalidated_at' => null,
        ]);
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
