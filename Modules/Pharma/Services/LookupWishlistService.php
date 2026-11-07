<?php

namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\LookupWishlist;

final class LookupWishlistService
{
    public const SOURCES = [
        'dav_price' => 'Giá thuốc tân dược',
        'traditional_price' => 'Giá thuốc đông dược',
        'dav_registration' => 'Công bố thuốc tân dược',
        'traditional_registration' => 'Công bố thuốc đông dược',
    ];

    public function savedIds(int $userId, string $source, array $ids): array
    {
        if ($ids === []) {
            return [];
        }

        return LookupWishlist::query()->where('user_id', $userId)
            ->where('source_type', $source)->whereIn('source_id', $ids)
            ->pluck('source_id')->all();
    }

    public function remove(int $userId, string $source, string $sourceId): bool
    {
        return LookupWishlist::query()->where('user_id', $userId)
            ->where('source_type', $source)->where('source_id', $sourceId)->delete() > 0;
    }

    public function save(int $userId, string $source, array $item, array $context): bool
    {
        if (! array_key_exists($source, self::SOURCES)) {
            throw ValidationException::withMessages(['source_type' => 'Nguồn tra cứu không hợp lệ.']);
        }
        $sourceId = $item['source_id'] ?? null;
        if (! is_scalar($sourceId) || trim((string) $sourceId) === ''
            || mb_strlen((string) $sourceId) > 160) {
            throw ValidationException::withMessages(['source_id' => 'Không có định danh thuốc hợp lệ từ nguồn.']);
        }
        $name = trim((string) ($item['name'] ?? ''));
        if ($name === '') {
            throw ValidationException::withMessages(['source_id' => 'Nguồn không cung cấp tên thuốc.']);
        }

        return DB::transaction(function () use ($userId, $source, $sourceId, $name, $item, $context): bool {
            // Serialize concurrent saves by user, in addition to the unique database constraint.
            $user = DB::table('users')->where('id', $userId)->lockForUpdate()->first(['id']);
            if ($user === null) {
                throw ValidationException::withMessages(['source_id' => 'Tài khoản không hợp lệ.']);
            }
            $exists = LookupWishlist::query()->where('user_id', $userId)
                ->where('source_type', $source)->where('source_id', (string) $sourceId)->exists();
            if ($exists) {
                return false;
            }
            LookupWishlist::query()->create([
                'user_id' => $userId,
                'source_type' => $source,
                'source_id' => (string) $sourceId,
                'medicine_name' => mb_substr($name, 0, 500),
                'registration_number' => mb_substr((string) ($item['registration'] ?? ''), 0, 160),
                'snapshot' => $item,
                'search_context' => $context,
            ]);

            return true;
        });
    }
}
