<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\Crypt;
use Modules\Muasamcong\Models\PersonalSession;
use Throwable;

class PersonalSessionService
{
    private const KEY = 'personal-page';

    public function cookie(): ?string
    {
        $record = PersonalSession::query()->where('key', self::KEY)->first();

        if ($record && is_string($record->cookie_encrypted) && $record->cookie_encrypted !== '') {
            try {
                return Crypt::decryptString($record->cookie_encrypted);
            } catch (Throwable) {
                // Fall through to environment fallback so a bad DB secret does not break the module.
            }
        }

        $fallback = trim((string) config('muasamcong.session_cookie'));

        return $fallback !== '' ? $fallback : null;
    }

    public function save(string $cookie, ?int $userId = null): void
    {
        $cookie = trim($cookie);

        if ($cookie === '') {
            throw new \InvalidArgumentException('Cookie Personal Page không được để trống.');
        }

        PersonalSession::query()->updateOrCreate(
            ['key' => self::KEY],
            [
                'cookie_encrypted' => Crypt::encryptString($cookie),
                'verified_at' => null,
                'last_failed_at' => null,
                'last_error' => null,
                'updated_by' => $userId,
            ]
        );
    }

    public function markVerified(): void
    {
        PersonalSession::query()->where('key', self::KEY)->update([
            'verified_at' => now(),
            'last_failed_at' => null,
            'last_error' => null,
        ]);
    }

    public function markFailed(string $message): void
    {
        PersonalSession::query()->where('key', self::KEY)->update([
            'last_failed_at' => now(),
            'last_error' => mb_substr($message, 0, 1000),
        ]);
    }

    public function status(): array
    {
        $record = PersonalSession::query()->where('key', self::KEY)->first();

        return [
            'has_database_session' => $record !== null,
            'has_session' => $this->cookie() !== null,
            'source' => $record ? 'database' : (filled(config('muasamcong.session_cookie')) ? 'env' : 'none'),
            'updated_at' => $record?->updated_at,
            'verified_at' => $record?->verified_at,
            'last_failed_at' => $record?->last_failed_at,
            'last_error' => $record?->last_error,
        ];
    }
}
