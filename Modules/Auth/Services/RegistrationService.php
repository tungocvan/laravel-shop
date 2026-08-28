<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\UserEmailVerification;
use Modules\Auth\Notifications\EmailVerificationOtpNotification;

class RegistrationService
{
    public const OTP_EXPIRES_MINUTES = 10;
    public const OTP_RESEND_COOLDOWN_SECONDS = 60;
    public const OTP_MAX_ATTEMPTS = 5;

    public function register(string $name, string $email, string $password): User
    {
        $email = $this->normalizeEmail($email);

        $user = DB::transaction(function () use ($name, $email, $password): User {
            $existing = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                throw ValidationException::withMessages([
                    'email' => 'Email này đã được sử dụng.',
                ]);
            }

            return User::query()->create([
                'name' => trim($name),
                'email' => $email,
                'password' => Hash::make($password),
                'is_active' => false,
                'email_verified_at' => null,
            ]);
        });

        $this->issueOtp($user, true);

        return $user;
    }

    public function resend(string $email): void
    {
        $user = $this->findPendingUser($email);
        $this->issueOtp($user);
    }

    public function verify(string $email, string $code): User
    {
        $user = $this->findPendingUser($email);

        $result = DB::transaction(function () use ($user, $code): array {
            /** @var UserEmailVerification|null $challenge */
            $challenge = UserEmailVerification::query()
                ->where('user_id', $user->getKey())
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $challenge || $challenge->expires_at->isPast()) {
                return ['error' => 'Mã OTP đã hết hạn. Vui lòng yêu cầu gửi lại mã mới.'];
            }

            if ($challenge->attempts >= self::OTP_MAX_ATTEMPTS) {
                return ['error' => 'Bạn đã nhập sai quá số lần cho phép. Vui lòng yêu cầu gửi lại OTP.'];
            }

            $challenge->increment('attempts');

            if (! hash_equals($challenge->code_hash, $this->hashOtp($user, $code))) {
                return ['error' => 'Mã OTP không chính xác.'];
            }

            $now = now();
            $challenge->forceFill(['verified_at' => $now])->save();
            $user->forceFill([
                'email_verified_at' => $now,
                'is_active' => true,
            ])->save();

            return ['user' => $user->refresh()];
        });

        if (isset($result['error'])) {
            throw ValidationException::withMessages([
                'otp' => $result['error'],
            ]);
        }

        return $result['user'];
    }

    private function issueOtp(User $user, bool $initial = false): void
    {
        $latest = UserEmailVerification::query()
            ->where('user_id', $user->getKey())
            ->whereNull('verified_at')
            ->whereNull('invalidated_at')
            ->latest('id')
            ->first();

        if (! $initial && $latest && $latest->last_sent_at->gt(now()->subSeconds(self::OTP_RESEND_COOLDOWN_SECONDS))) {
            throw ValidationException::withMessages([
                'otp' => 'Vui lòng chờ trước khi yêu cầu gửi lại OTP.',
            ]);
        }

        $otp = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($user, $otp): void {
            UserEmailVerification::query()
                ->where('user_id', $user->getKey())
                ->whereNull('verified_at')
                ->whereNull('invalidated_at')
                ->update(['invalidated_at' => now()]);

            UserEmailVerification::query()->create([
                'user_id' => $user->getKey(),
                'email' => $this->normalizeEmail($user->email),
                'code_hash' => $this->hashOtp($user, $otp),
                'expires_at' => now()->addMinutes(self::OTP_EXPIRES_MINUTES),
                'last_sent_at' => now(),
                'attempts' => 0,
            ]);
        });

        $user->notify(new EmailVerificationOtpNotification($otp, self::OTP_EXPIRES_MINUTES));
    }

    private function findPendingUser(string $email): User
    {
        $email = $this->normalizeEmail($email);

        /** @var User|null $user */
        $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            throw ValidationException::withMessages([
                'email' => 'Không tìm thấy tài khoản đang chờ xác minh.',
            ]);
        }

        if ($user->is_active && $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => 'Tài khoản này đã được xác minh.',
            ]);
        }

        return $user;
    }

    private function hashOtp(User $user, string $otp): string
    {
        $key = (string) config('app.key');

        if (Str::startsWith($key, 'base64:')) {
            $decoded = base64_decode(Str::after($key, 'base64:'), true);
            if ($decoded !== false) {
                $key = $decoded;
            }
        }

        return hash_hmac(
            'sha256',
            $user->getKey().'|'.$this->normalizeEmail($user->email).'|'.trim($otp),
            $key,
        );
    }

    private function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
