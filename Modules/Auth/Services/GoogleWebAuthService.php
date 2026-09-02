<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Modules\Auth\Models\UserEmailVerification;

class GoogleWebAuthService
{
    public function resolve(object $googleUser): User
    {
        return $this->resolveIdentity($googleUser, allowCreate: true);
    }

    public function resolveExisting(object $googleUser): User
    {
        return $this->resolveIdentity($googleUser, allowCreate: false);
    }

    public function link(User $user, object $googleUser): User
    {
        [$googleId, $email] = $this->verifiedIdentity($googleUser);

        return DB::transaction(function () use ($user, $googleId, $email): User {
            $current = User::query()->lockForUpdate()->findOrFail($user->getKey());

            if (! $current->is_active || mb_strtolower(trim((string) $current->email)) !== $email) {
                throw ValidationException::withMessages([
                    'email' => 'Email Google phải trùng với email của tài khoản đang đăng nhập.',
                ]);
            }

            $googleOwner = User::withTrashed()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($googleOwner && ! $googleOwner->is($current)) {
                throw ValidationException::withMessages([
                    'email' => 'Tài khoản Google này đã được liên kết với người dùng khác.',
                ]);
            }

            if ($current->google_id && $current->google_id !== $googleId) {
                throw ValidationException::withMessages([
                    'email' => 'Tài khoản này đã liên kết với một tài khoản Google khác.',
                ]);
            }

            $current->forceFill([
                'google_id' => $googleId,
                'email_verified_at' => $current->email_verified_at ?: now(),
            ])->save();

            return $current->refresh();
        });
    }

    private function resolveIdentity(object $googleUser, bool $allowCreate): User
    {
        [$googleId, $email] = $this->verifiedIdentity($googleUser);

        return DB::transaction(function () use ($googleUser, $googleId, $email, $allowCreate): User {
            $linked = User::withTrashed()
                ->where('google_id', $googleId)
                ->lockForUpdate()
                ->first();

            if ($linked) {
                $emailOwner = User::withTrashed()
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->lockForUpdate()
                    ->first();

                if ($emailOwner && ! $emailOwner->is($linked)) {
                    throw ValidationException::withMessages([
                        'email' => 'Danh tính Google xung đột với một tài khoản khác.',
                    ]);
                }

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
                if ($emailOwner->trashed() || ! $emailOwner->is_active || ! $emailOwner->email_verified_at) {
                    throw ValidationException::withMessages([
                        'email' => 'Email này đã có tài khoản nhưng chưa đủ điều kiện liên kết Google tự động.',
                    ]);
                }

                if ($emailOwner->google_id && $emailOwner->google_id !== $googleId) {
                    throw ValidationException::withMessages([
                        'email' => 'Tài khoản này đã liên kết với một tài khoản Google khác.',
                    ]);
                }

                $otpVerified = UserEmailVerification::query()
                    ->where('user_id', $emailOwner->getKey())
                    ->whereRaw('LOWER(email) = ?', [$email])
                    ->where('verified_at', $emailOwner->email_verified_at)
                    ->whereNull('invalidated_at')
                    ->exists();

                if (! $otpVerified) {
                    throw ValidationException::withMessages([
                        'email' => 'Hãy đăng nhập bằng mật khẩu trước khi liên kết Google với tài khoản hiện có.',
                    ]);
                }

                $emailOwner->forceFill(['google_id' => $googleId])->save();

                return $emailOwner->refresh();
            }

            if (! $allowCreate) {
                throw ValidationException::withMessages([
                    'email' => 'Tài khoản Google này chưa được đăng ký trong hệ thống quản trị.',
                ]);
            }

            $user = User::query()->create([
                'name' => trim((string) $googleUser->getName()) ?: $email,
                'email' => $email,
                'password' => Hash::make(Str::random(64)),
                'google_id' => $googleId,
                'avatar' => $googleUser->getAvatar(),
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            return $user->refresh();
        });
    }

    private function verifiedIdentity(object $googleUser): array
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

        return [$googleId, $email];
    }
}
