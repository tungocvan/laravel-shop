<?php

namespace Modules\Auth\Services;

use App\Models\User;
use Exception;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class AuthService
{
    public function handleGoogleUser($googleUser)
    {
        $email = mb_strtolower(trim((string) $googleUser->email));

        $user = DB::transaction(function () use ($googleUser, $email): User {
            $user = User::withTrashed()
                ->where(function ($query) use ($googleUser, $email): void {
                    $query->where('google_id', (string) $googleUser->id)
                        ->orWhereRaw('LOWER(email) = ?', [$email]);
                })
                ->lockForUpdate()
                ->first();

            if ($user) {
                if ($user->trashed()) {
                    $user->restore();
                }

                $attributes = [
                    'name' => $user->name ?: $googleUser->name,
                    'email' => $email,
                    'google_id' => (string) $googleUser->id,
                    'google_token' => $googleUser->token,
                    'avatar' => $user->avatar ?: $googleUser->avatar,
                ];

                if (filled($googleUser->refreshToken)) {
                    $attributes['google_refresh_token'] = $googleUser->refreshToken;
                }

                $user->update($attributes);
            } else {
                $user = User::query()->create([
                    'name' => $googleUser->name ?: $email,
                    'email' => $email,
                    'google_id' => (string) $googleUser->id,
                    'google_token' => $googleUser->token,
                    'google_refresh_token' => $googleUser->refreshToken,
                    'avatar' => $googleUser->avatar,
                    'password' => Hash::make(Str::random(40)),
                    'is_active' => true,
                ]);
            }

            if (! $user->roles()->where('guard_name', 'admin')->exists()) {
                $role = Role::query()->firstOrCreate([
                    'name' => 'User',
                    'guard_name' => 'admin',
                ]);
                $user->assignRole($role);
            }

            return $user;
        });

        if (! $user->is_active) {
            throw new Exception('Tài khoản của bạn đã bị khóa.');
        }

        Auth::guard('admin')->login($user);
        $user->update(['last_login_at' => now()]);

        return $user;
    }
}
