<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\SessionImportToken;
use RuntimeException;

class SessionImportTokenService
{
    public function create(?int $userId = null, int $ttlMinutes = 5, ?string $baseUrl = null): array
    {
        $token = Str::random(64);
        $expiresAt = now()->addMinutes($ttlMinutes);

        SessionImportToken::query()->create([
            'token_hash' => hash('sha256', $token),
            'created_by' => $userId,
            'expires_at' => $expiresAt,
        ]);

        SessionImportToken::query()
            ->whereNull('used_at')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $base = rtrim($baseUrl ?: (string) config('app.url'), '/');

        return [
            'token' => $token,
            // Token is in the URL fragment, so it is not sent in HTTP access logs or Referer headers.
            'link' => $base.'/admin/muasamcong/update-cookie#'.$token,
            'expires_at' => $expiresAt,
        ];
    }

    public function validate(string $plainToken): SessionImportToken
    {
        $hash = hash('sha256', trim($plainToken));
        $record = SessionImportToken::query()->where('token_hash', $hash)->first();

        if (! $record || $record->used_at !== null || $record->expires_at?->isPast()) {
            throw new RuntimeException('Mã cập nhật Session không hợp lệ, đã dùng hoặc đã hết hạn.');
        }

        return $record;
    }

    public function consume(string $plainToken): SessionImportToken
    {
        $hash = hash('sha256', trim($plainToken));

        return DB::transaction(function () use ($hash): SessionImportToken {
            $record = SessionImportToken::query()
                ->where('token_hash', $hash)
                ->lockForUpdate()
                ->first();

            if (! $record || $record->used_at !== null || $record->expires_at?->isPast()) {
                throw new RuntimeException('Mã cập nhật Session không hợp lệ, đã dùng hoặc đã hết hạn.');
            }

            $record->forceFill(['used_at' => now()])->save();

            return $record;
        });
    }
}
