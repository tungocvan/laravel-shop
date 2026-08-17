<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Modules\Muasamcong\Models\SessionImportToken;
use RuntimeException;

class SessionImportTokenService
{
    public function create(?int $userId = null, int $ttlMinutes = 5): array
    {
        $token = Str::random(64);

        SessionImportToken::query()->create([
            'token_hash' => hash('sha256', $token),
            'created_by' => $userId,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ]);

        SessionImportToken::query()
            ->whereNull('used_at')
            ->where('expires_at', '<', now()->subDay())
            ->delete();

        $base = rtrim((string) config('app.url'), '/');

        return [
            'token' => $token,
            // Token is placed in URL fragment. Browsers/proxies do not send fragments to the server.
            'link' => $base.'/admin/muasamcong/update-cookie#'.$token,
            'expires_at' => now()->addMinutes($ttlMinutes),
        ];
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
