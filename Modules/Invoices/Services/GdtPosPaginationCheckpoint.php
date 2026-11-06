<?php

namespace Modules\Invoices\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Session-scoped, short-lived POS pagination metadata.
 *
 * No invoice payloads, access tokens, cookies, CAPTCHA answers, or GDT cursor
 * are stored. This is not a resumable upstream request until the GDT
 * continuation contract has been verified.
 */
final class GdtPosPaginationCheckpoint
{
    private const TTL_SECONDS = 300;

    public function create(string $sessionId, string $source, string $fromDate, string $toDate, int $received, int $total): array
    {
        if ($sessionId === '' || $source !== 'pos') {
            throw new \InvalidArgumentException('Invalid POS session or source.');
        }

        $progress = new GdtPosPaginationProgress($received, $total);
        if (! $progress->needsNextPage()) {
            throw new \InvalidArgumentException('Only incomplete POS searches can be checkpointed.');
        }

        $id = (string) Str::uuid();
        $metadata = [
            'source' => $source,
            'from' => $fromDate,
            'to' => $toDate,
            'received' => $received,
            'total' => $total,
            'next_page' => $progress->nextPageNumber(),
            'expires_at' => now()->addSeconds(self::TTL_SECONDS)->toIso8601String(),
        ];
        Cache::put($this->key($sessionId, $id), $metadata, self::TTL_SECONDS);

        return ['id' => $id] + $metadata;
    }

    public function find(string $sessionId, string $id): ?array
    {
        if ($sessionId === '' || ! Str::isUuid($id)) {
            return null;
        }

        $value = Cache::get($this->key($sessionId, $id));

        return is_array($value) ? $value : null;
    }

    public function discard(string $sessionId, string $id): void
    {
        if ($sessionId !== '' && Str::isUuid($id)) {
            Cache::forget($this->key($sessionId, $id));
        }
    }

    private function key(string $sessionId, string $id): string
    {
        return 'invoices:pos:checkpoint:'.hash('sha256', $sessionId).':'.$id;
    }
}
