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

    public function test_admin_google_callback_uses_admin_guard_without_provisioning_roles(): void
    {
        $googleUser = $this->googleUser('admin-google-100', 'admin-google@example.com');
        $provider = Mockery::mock();
        $provider->shouldReceive('user')->once()->andReturn($googleUser);
        Socialite::shouldReceive('driver')->once()->with('google')->andReturn($provider);

        $this->get(route('google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $user = User::query()->where('google_id', 'admin-google-100')->sole();

        $this->assertAuthenticatedAs($user, 'admin');
        $this->assertGuest('web');
        $this->assertNotNull($user->last_login_at);
        $this->assertCount(0, $user->roles);
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
