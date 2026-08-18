<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Str;
use Modules\Muasamcong\Models\PricingSearchSnapshot;

class PricingSearchSnapshotService
{
    public function find(string $keyword): ?PricingSearchSnapshot
    {
        $snapshot = PricingSearchSnapshot::query()
            ->where('keyword_hash', $this->hash($keyword))
            ->first();

        if ($snapshot === null) {
            return null;
        }

        $snapshot->forceFill([
            'last_accessed_at' => now(),
            'access_count' => ((int) $snapshot->access_count) + 1,
        ])->save();

        return $snapshot->refresh();
    }

    public function store(string $keyword, array $result, ?int $userId): PricingSearchSnapshot
    {
        $items = is_array($result['data']['items'] ?? null) ? $result['data']['items'] : [];
        $sourceTotal = max(count($items), (int) ($result['data']['total'] ?? count($items)));
        $sourcePartial = (bool) ($result['data']['partial'] ?? false)
            || (bool) ($result['data']['capped'] ?? false);

        return PricingSearchSnapshot::query()->updateOrCreate(
            ['keyword_hash' => $this->hash($keyword)],
            [
                'keyword' => trim($keyword),
                'keyword_normalized' => $this->normalize($keyword),
                'result_payload' => $result,
                'source_total' => $sourceTotal,
                'loaded_total' => count($items),
                'source_partial' => $sourcePartial,
                'searched_by' => $userId,
                'searched_at' => now(),
                'last_accessed_at' => now(),
                'access_count' => 1,
            ]
        );
    }

    public function recent(int $limit = 8): array
    {
        return PricingSearchSnapshot::query()
            ->orderByDesc('searched_at')
            ->limit(max(1, min(20, $limit)))
            ->get()
            ->map(fn (PricingSearchSnapshot $snapshot): array => [
                'keyword' => $snapshot->keyword,
                'searched_at' => $snapshot->searched_at?->toIso8601String(),
                'loaded_total' => (int) $snapshot->loaded_total,
                'source_total' => (int) $snapshot->source_total,
            ])
            ->all();
    }

    public function delete(string $keyword): bool
    {
        return PricingSearchSnapshot::query()
            ->where('keyword_hash', $this->hash($keyword))
            ->delete() > 0;
    }

    public function clear(): int
    {
        return PricingSearchSnapshot::query()->delete();
    }

    private function hash(string $keyword): string
    {
        return hash('sha256', $this->normalize($keyword));
    }

    private function normalize(string $keyword): string
    {
        $value = Str::lower(Str::ascii(trim($keyword)));
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }
}
