<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AdminGoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_google_callback_uses_existing_account_without_provisioning_roles(): void
    {
        $existing = User::query()->create([
            'name' => 'Existing Admin Google User',
            'email' => 'admin-google@example.com',
            'password' => Hash::make('Password123!'),
            'google_id' => 'admin-google-100',
            'is_active' => true,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->googleUser('admin-google-100', 'admin-google@example.com'),
        );
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $user = User::query()->where('google_id', 'admin-google-100')->sole();

        $this->assertTrue($user->is($existing));
        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertGuest('web');
        $this->assertNotNull($user->last_login_at);
        $this->assertCount(0, $user->roles);
    }

    public function test_admin_google_callback_uses_one_time_auto_link_approval_for_existing_email(): void
    {
        $existing = User::query()->create([
            'name' => 'Approved Google Link User',
            'email' => 'approved-link@example.com',
            'password' => Hash::make('Password123!'),
            'google_auto_link_enabled' => true,
            'is_active' => true,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->googleUser('admin-google-approved', 'approved-link@example.com'),
        );
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $existing->refresh();

        $this->assertSame('admin-google-approved', $existing->google_id);
        $this->assertFalse($existing->google_auto_link_enabled);
        $this->assertNotNull($existing->email_verified_at);
        $this->assertAuthenticatedAs($existing, 'admin');
    }

    public function test_admin_google_callback_still_blocks_unapproved_existing_email(): void
    {
        $existing = User::query()->create([
            'name' => 'Unapproved Google Link User',
            'email' => 'unapproved-link@example.com',
            'password' => Hash::make('Password123!'),
            'google_auto_link_enabled' => false,
            'is_active' => true,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->googleUser('admin-google-unapproved', 'unapproved-link@example.com'),
        );
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $existing->refresh();

        $this->assertNull($existing->google_id);
        $this->assertFalse($existing->google_auto_link_enabled);
        $this->assertGuest('admin');
    }

    public function test_admin_google_callback_does_not_create_unknown_account(): void
    {
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->googleUser('admin-google-new', 'new-admin@example.com'),
        );
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertDatabaseMissing('users', ['email' => 'new-admin@example.com']);
    }

    public function test_admin_google_callback_does_not_restore_soft_deleted_account(): void
    {
        $deleted = User::query()->create([
            'name' => 'Deleted Google User',
            'email' => 'deleted-google@example.com',
            'password' => Hash::make('Password123!'),
            'google_id' => 'admin-google-deleted',
            'is_active' => true,
        ]);
        $deleted->delete();

        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn(
            $this->googleUser('admin-google-deleted', 'deleted-google@example.com'),
        );
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest('admin');
        $this->assertNotNull(User::withTrashed()->find($deleted->getKey())?->deleted_at);
    }

    private function googleUser(string $id, string $email): SocialiteUser
    {
        return (new SocialiteUser)
            ->setRaw([
                'sub' => $id,
                'email' => $email,
                'email_verified' => true,
                'name' => 'Admin Google User',
                'picture' => 'https://example.com/avatar.png',
            ])
            ->map([
                'id' => $id,
                'name' => 'Admin Google User',
                'email' => $email,
                'avatar' => 'https://example.com/avatar.png',
            ]);
    }
}
