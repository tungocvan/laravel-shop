<?php

namespace Tests\Feature\ClientApps;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Modules\Auth\Livewire\Auth\RegistrationForm;
use Modules\Auth\Livewire\Auth\VerifyEmailOtpForm;
use Modules\Auth\Notifications\EmailVerificationOtpNotification;
use Tests\TestCase;

class ClientPortalPwaRegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pwa_registration_and_verification_pages_are_available_to_guests(): void
    {
        $this->get('/my-apps/register')
            ->assertOk()
            ->assertSee('Tạo tài khoản');

        $this->withSession(['auth.pending_verification_email' => 'portal@example.com'])
            ->get('/my-apps/verify-email')
            ->assertOk()
            ->assertSee('Xác minh email');
    }

    public function test_registration_form_creates_pending_account_and_redirects_to_otp_page(): void
    {
        Notification::fake();

        Livewire::test(RegistrationForm::class)
            ->set('name', 'Portal User')
            ->set('email', 'portal@example.com')
            ->set('password', 'StrongPassword123!')
            ->set('password_confirmation', 'StrongPassword123!')
            ->call('register')
            ->assertRedirect(route('client.apps.verify-email'));

        $user = User::query()->where('email', 'portal@example.com')->sole();
        $this->assertFalse($user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('portal@example.com', session('auth.pending_verification_email'));
    }

    public function test_valid_otp_logs_user_into_web_guard_and_redirects_to_client_apps(): void
    {
        Notification::fake();

        Livewire::test(RegistrationForm::class)
            ->set('name', 'Portal User')
            ->set('email', 'portal@example.com')
            ->set('password', 'StrongPassword123!')
            ->set('password_confirmation', 'StrongPassword123!')
            ->call('register');

        $user = User::query()->where('email', 'portal@example.com')->sole();
        $otp = null;

        Notification::assertSentTo(
            $user,
            EmailVerificationOtpNotification::class,
            function (EmailVerificationOtpNotification $notification) use (&$otp): bool {
                $otp = $notification->code;

                return true;
            },
        );

        Livewire::withSession(['auth.pending_verification_email' => 'portal@example.com'])
            ->test(VerifyEmailOtpForm::class)
            ->set('otp', $otp)
            ->call('verify')
            ->assertRedirect(route('client.apps.index'));

        $this->assertAuthenticatedAs($user->refresh(), 'web');
        $this->assertTrue($user->refresh()->is_active);
        $this->assertNotNull($user->email_verified_at);
    }
}
