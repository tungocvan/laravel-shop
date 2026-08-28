<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class GoogleWebAuthService
{
    public function resolve(object $googleUser): User
    {
        $googleId = trim((string) $googleUser->getId());
        $email = mb_strtolower(trim((string) $googleUser->getEmail()));
        $raw = is_array($googleUser->user ?? null) ? $googleUser->user : [];
        $verified = filter_var(
            $raw['email_verified'] ?? $raw['verified_email'] ?? false,
            FILTER_VALIDATE_BOOL,
        );

        if ($googleId === '' || $email === '' || ! $verified) {
            throw ValidationException::withMessages([
                'email' => 'Google chưa xác nhận email hợp lệ cho tài khoản này.',
            ]);
        }

        return DB::transaction(function () use ($googleUser, $googleId, $email): User {
            $linked = User::withTrashed()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($linked) {
                if ($linked->trashed() || ! $linked->is_active) {
                    throw ValidationException::withMessages([
                        'email' => 'Tài khoản đã liên kết Google hiện không hoạt động.',
                    ]);
                }

                return $linked;
            }

            $emailOwner = User::withTrashed()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->lockForUpdate()
                ->first();

            if ($emailOwner) {
                throw ValidationException::withMessages([
                    'email' => 'Email này đã có tài khoản. Hãy đăng nhập bằng mật khẩu trước khi liên kết Google.',
                ]);
            }

            return User::query()->create([
                'name' => trim((string) $googleUser->getName()) ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                'is_active' => true,
                'email_verified_at' => now(),
            ]);
        });
    }
}
