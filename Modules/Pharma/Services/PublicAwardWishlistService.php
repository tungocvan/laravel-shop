<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\AwardWishlist;

final class PublicAwardWishlistService
{
    public function savedIds(int $userId, array $sourceIds): array
    {
        return AwardWishlist::query()->where('user_id', $userId)
            ->whereIn('source_id', $sourceIds)->pluck('source_id')->all();
    }

    public function toggle(int $userId, array $item, string $keyword, int $page): bool
    {
        $sourceId = $item['id'] ?? null;
        if (! is_scalar($sourceId) || trim((string) $sourceId) === '') {
            throw ValidationException::withMessages(['source_id' => 'Bản ghi nguồn không hợp lệ.']);
        }
        $sourceId = (string) $sourceId;

        return DB::transaction(function () use ($userId, $sourceId, $item, $keyword, $page): bool {
            // Per-user/source lock prevents duplicate inserts under concurrent requests.
            $lock = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id']);
            if ($lock === null) {
                throw ValidationException::withMessages(['source_id' => 'Tài khoản không tồn tại.']);
            }

            $existing = AwardWishlist::query()->where('user_id', $userId)
                ->where('source_id', $sourceId)->first();
            if ($existing !== null) {
                $existing->delete();
                return false;
            }

            AwardWishlist::query()->create([
                'user_id' => $userId,
                'source_id' => $sourceId,
                'search_keyword' => $keyword,
                'source_page' => $page,
                'medicine_name' => mb_substr((string) ($item['tenThuoc'] ?? ''), 0, 500),
                'medicine_group' => mb_substr((string) ($item['nhomThuoc'] ?? ''), 0, 255),
                'snapshot' => $item,
            ]);
            return true;
        });
    }
}
