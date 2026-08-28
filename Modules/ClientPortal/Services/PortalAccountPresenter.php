<?php

namespace Modules\ClientPortal\Services;

use App\Models\User;

class PortalAccountPresenter
{
    public function for(?User $user): array
    {
        if (! $user) {
            return [];
        }

        $name = trim((string) $user->name) ?: 'Tài khoản';
        $email = trim((string) $user->email);
        $avatar = trim((string) $user->avatar);

        return [
            'name' => $name,
            'email' => $email,
            'phone' => filled($user->phone) ? trim((string) $user->phone) : null,
            'initials' => $this->initials($name),
            'avatar_url' => $this->avatarUrl($avatar),
            'email_verified' => $user->email_verified_at !== null,
            'google_linked' => filled($user->google_id),
        ];
    }

    private function initials(string $name): string
    {
        $parts = preg_split('/\s+/u', trim($name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($parts === []) {
            return 'TK';
        }

        $first = mb_substr($parts[0], 0, 1);
        $last = count($parts) > 1 ? mb_substr($parts[array_key_last($parts)], 0, 1) : '';

        return mb_strtoupper($first.$last);
    }

    private function avatarUrl(string $avatar): ?string
    {
        if ($avatar === '') {
            return null;
        }

        if (str_starts_with($avatar, 'https://') || str_starts_with($avatar, 'http://')) {
            return $avatar;
        }

        if (str_contains($avatar, '://')) {
            return null;
        }

        return asset('storage/'.ltrim($avatar, '/'));
    }
}
