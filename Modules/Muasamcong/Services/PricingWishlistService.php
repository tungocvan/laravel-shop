<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Support\Str;
use Modules\Muasamcong\Models\PricingWishlist;

class PricingWishlistService
{
    public function sourceIdsForUser(int $userId, array $results): array
    {
        $ids = collect($results)
            ->pluck('id')
            ->filter(fn (mixed $id): bool => is_string($id) && Str::isUuid($id))
            ->unique()
            ->values();

        if ($ids->isEmpty()) {
            return [];
        }

        return PricingWishlist::query()
            ->where('user_id', $userId)
            ->whereIn('source_id', $ids->all())
            ->pluck('source_id')
            ->all();
    }

    public function toggle(int $userId, string $searchKeyword, array $item): bool
    {
        $sourceId = (string) ($item['id'] ?? '');

        if (! Str::isUuid($sourceId)) {
            return false;
        }

        $existing = PricingWishlist::query()
            ->where('user_id', $userId)
            ->where('source_id', $sourceId)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        PricingWishlist::query()->create([
            'user_id' => $userId,
            'source_id' => $sourceId,
            'search_keyword' => Str::limit(trim($searchKeyword), 200, ''),
            'medicine_name' => $this->string($item['tenThuoc'] ?? null, 500),
            'active_ingredient' => $this->string($item['tenHoatChat'] ?? null, 500),
            'strength' => $this->string($item['nongDo'] ?? null, 500),
            'medicine_group' => $this->string($item['nhomThuoc'] ?? null, 255),
            'ma_tbmt' => $this->string($item['maTbmt'] ?? null, 100),
            'snapshot' => $item,
        ]);

        return true;
    }

    public function recentForUser(int $userId, int $limit = 20): array
    {
        return PricingWishlist::query()
            ->where('user_id', $userId)
            ->latest('updated_at')
            ->limit(max(1, min(50, $limit)))
            ->get()
            ->map(fn (PricingWishlist $item): array => [
                'id' => $item->id,
                'source_id' => $item->source_id,
                'search_keyword' => $item->search_keyword,
                'medicine_name' => $item->medicine_name,
                'active_ingredient' => $item->active_ingredient,
                'strength' => $item->strength,
                'medicine_group' => $item->medicine_group,
                'ma_tbmt' => $item->ma_tbmt,
            ])
            ->all();
    }

    private function string(mixed $value, int $max): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $value = trim((string) $value);

        return $value === '' ? null : Str::limit($value, $max, '');
    }
}
