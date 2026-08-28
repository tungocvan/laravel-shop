<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\UserEmailVerification;
use Modules\Auth\Notifications\EmailVerificationOtpNotification;
use Modules\Auth\Services\RegistrationService;
use Tests\TestCase;

class RegistrationOtpTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_creates_inactive_user_and_stores_only_hashed_otp(): void
    {
        Notification::fake();

        $user = app(RegistrationService::class)->register(
            'Portal User',
            'Portal.User@example.com',
            'StrongPassword123!',
        );

        $this->assertFalse($user->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertSame('portal.user@example.com', $user->email);

        $challenge = UserEmailVerification::query()->where('user_id', $user->id)->sole();
        $this->assertSame(64, strlen($challenge->code_hash));

        Notification::assertSentTo(
            $user,
            EmailVerificationOtpNotification::class,
            function (EmailVerificationOtpNotification $notification) use ($challenge): bool {
                $this->assertMatchesRegularExpression('/^\d{6}$/', $notification->code);
                $this->assertNotSame($notification->code, $challenge->code_hash);

                return true;
            },
        );
    }

    public function test_valid_otp_verifies_email_and_activates_account(): void
    {
        Notification::fake();

        $service = app(RegistrationService::class);
        $user = $service->register('Portal User', 'portal@example.com', 'StrongPassword123!');
        $otp = null;

        Notification::assertSentTo(
            $user,
            EmailVerificationOtpNotification::class,
            function (EmailVerificationOtpNotification $notification) use (&$otp): bool {
                $otp = $notification->code;

                return true;
            },
        );

        $verified = $service->verify($user->email, (string) $otp);

        $this->assertTrue($verified->is_active);
        $this->assertNotNull($verified->email_verified_at);
        $this->assertNotNull(
            UserEmailVerification::query()->where('user_id', $user->id)->sole()->verified_at,
        );
    }

    public function test_wrong_otp_does_not_activate_account(): void
    {
        Notification::fake();

        $service = app(RegistrationService::class);
        $user = $service->register('Portal User', 'wrong@example.com', 'StrongPassword123!');

        try {
            $service->verify($user->email, '000000');
            $this->fail('Expected OTP validation to fail.');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('otp', $e->errors());
        }

        $this->assertFalse($user->refresh()->is_active);
        $this->assertNull($user->email_verified_at);
        $this->assertSame(1, UserEmailVerification::query()->where('user_id', $user->id)->sole()->attempts);
    }

    public function test_resend_is_rate_limited_by_cooldown(): void
    {
        Notification::fake();

        $service = app(RegistrationService::class);
        $user = $service->register('Portal User', 'cooldown@example.com', 'StrongPassword123!');

        $this->expectException(ValidationException::class);
        $service->resend($user->email);
    }

    public function test_existing_email_cannot_be_registered_again(): void
    {
        Notification::fake();

        User::query()->create([
            'name' => 'Existing User',
            'email' => 'existing@example.com',
            'password' => bcrypt('password'),
            'is_active' => true,
        ]);

        $this->expectException(ValidationException::class);

        app(RegistrationService::class)->register(
            'Duplicate User',
            'EXISTING@example.com',
            'StrongPassword123!',
        );
    }
}
