<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Str;

/**
 * Server-side, encrypted, single-use POS continuation.
 *
 * The cursor and first-page invoices are never exposed as Livewire public
 * properties or written to logs. Scope is bound to admin + browser session
 * + filter fingerprint. A failed/expired continuation cannot unlock sync.
 */
final class GdtPosSecureContinuation
{
    private const TTL_SECONDS = 300;

    public function store(
        int $adminId,
        string $sessionId,
        string $fingerprint,
        array $firstPage,
        string $cursor,
        int $total,
    ): string {
        if ($adminId <= 0 || $sessionId === '' || $fingerprint === ''
            || $cursor === '' || $total <= 0 || count($firstPage) !== 15 || $total <= 15) {
            throw new \InvalidArgumentException('Invalid incomplete POS page checkpoint.');
        }

        $id = (string) Str::uuid();
        $payload = [
            'fingerprint' => $fingerprint,
            'cursor' => $cursor,
            'first_page' => $firstPage,
            'total' => $total,
        ];
        Cache::put(
            $this->key($adminId, $sessionId, $id),
            Crypt::encryptString(json_encode($payload, JSON_THROW_ON_ERROR)),
            self::TTL_SECONDS
        );

        return $id;
    }

    public function consume(int $adminId, string $sessionId, string $fingerprint, string $id): ?array
    {
        if ($adminId <= 0 || $sessionId === '' || $fingerprint === '' || ! Str::isUuid($id)) {
            return null;
        }

        $key = $this->key($adminId, $sessionId, $id);

        // Serialize competing claims across PHP workers. The lock key is
        // scoped exactly like the encrypted payload and contains no secrets.
        return Cache::lock($key.':claim', 10)->block(3, function () use ($key, $fingerprint): ?array {
            $encrypted = Cache::get($key);
            if (! is_string($encrypted)) {
                return null;
            }

            try {
                $payload = json_decode(Crypt::decryptString($encrypted), true, 512, JSON_THROW_ON_ERROR);
            } catch (\Throwable) {
                return null;
            }

            if (! is_array($payload) || ($payload['fingerprint'] ?? null) !== $fingerprint
                || ! is_string($payload['cursor'] ?? null) || $payload['cursor'] === ''
                || ! is_array($payload['first_page'] ?? null)
                || count($payload['first_page']) !== 15
                || ! is_int($payload['total'] ?? null) || $payload['total'] <= 15) {
                return null;
            }

            Cache::forget($key);

            return [
                'cursor' => $payload['cursor'],
                'first_page' => $payload['first_page'],
                'total' => $payload['total'],
            ];
        });
    }

    public function discard(int $adminId, string $sessionId, string $id): void
    {
        if ($adminId > 0 && $sessionId !== '' && Str::isUuid($id)) {
            Cache::forget($this->key($adminId, $sessionId, $id));
        }
    }

    private function key(int $adminId, string $sessionId, string $id): string
    {
        return 'invoices:gdt:pos-secure-continuation:'.$adminId.':'.hash('sha256', $sessionId).':'.$id;
    }
}
