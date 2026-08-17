<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;
use Modules\Muasamcong\Models\PersonalSession;
use RuntimeException;
use Throwable;

class PersonalSessionService
{
    private const KEY = 'personal-page';

    public function cookie(): ?string
    {
        $record = $this->record();

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

        if (! $this->tableExists()) {
            throw new RuntimeException('Chưa có bảng muasamcong_personal_sessions. Hãy chạy php artisan migrate.');
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
        if (! $this->tableExists()) {
            return;
        }

        PersonalSession::query()->where('key', self::KEY)->update([
            'verified_at' => now(),
            'last_failed_at' => null,
            'last_error' => null,
        ]);
    }

    public function markFailed(string $message): void
    {
        if (! $this->tableExists()) {
            return;
        }

        PersonalSession::query()->where('key', self::KEY)->update([
            'last_failed_at' => now(),
            // Never persist upstream exception text here: HTTP/header exceptions may contain
            // the full Cookie header or other authentication material.
            'last_error' => 'Personal Page Session verification failed. Check or refresh the saved session.',
        ]);
    }

    public function status(): array
    {
        $record = $this->record();

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

    private function record(): ?PersonalSession
    {
        return $this->tableExists()
            ? PersonalSession::query()->where('key', self::KEY)->first()
            : null;
    }

    private function tableExists(): bool
    {
        return Schema::hasTable('muasamcong_personal_sessions');
    }
}
