<?php

namespace Modules\ClientPortal\Applications\Pharma\Services;

use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * Resolves an exact KQLCNT group into Pharma product IDs without TBMT-wide
 * expansion. The result is safe for downstream ID-scoped queries only.
 */
final class KqlcntCanonicalProductScope
{
    /**
     * @param Collection<int, object> $canonical
     * @param Collection<int, object> $linked
     * @return list<int>
     */
    public function resolve(Collection $canonical, Collection $linked): array
    {
        $invalid = static fn () => ValidationException::withMessages([
            'award' => 'Hồ sơ KQLCNT chưa có liên kết sản phẩm Pharma duy nhất và chính xác.',
        ]);

        if ($canonical->isEmpty() || $canonical->contains(fn ($row) => (int) $row->id <= 0)) {
            throw $invalid();
        }

        $byCanonicalId = $canonical->keyBy(fn ($row) => (int) $row->id);
        $bySource = $linked->groupBy(fn ($row) => (int) $row->source_id);

        if ($byCanonicalId->count() !== $canonical->count()
            || $linked->count() !== $canonical->count()
            || $bySource->count() !== $canonical->count()
            || $bySource->keys()->diff($byCanonicalId->keys())->isNotEmpty()
            || $linked->pluck('id')->unique()->count() !== $linked->count()
        ) {
            throw $invalid();
        }

        foreach ($canonical as $line) {
            $matches = $bySource->get((int) $line->id);
            if ($matches === null || $matches->count() !== 1) {
                throw $invalid();
            }

            $product = $matches->first();
            if ((int) $product->id <= 0
                || trim((string) $product->bidding_notice_code) !== trim((string) $line->notify_no)
                || trim((string) $product->contractor_code) !== trim((string) $line->contractor_code)
            ) {
                throw $invalid();
            }
        }

        return $canonical->map(fn ($line) => (int) $bySource->get((int) $line->id)->first()->id)->all();
    }
}
