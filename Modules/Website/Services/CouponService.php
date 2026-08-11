<?php

namespace Modules\Website\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Modules\Website\Models\Coupon;

class CouponService
{
    public function find(int $id): Coupon
    {
        return Coupon::query()->findOrFail($id);
    }

    public function save(?int $id, array $data): Coupon
    {
        $data['code'] = strtoupper($data['code']);

        return Coupon::query()->updateOrCreate(['id' => $id], $data);
    }

    public function paginate(string $search, int $perPage): LengthAwarePaginator
    {
        return $this->query($search)->paginate($perPage);
    }

    public function all(string $search): Collection
    {
        return $this->query($search)->get();
    }

    public function ids(string $search): array
    {
        return $this->query($search)->pluck('id')->map(fn ($id) => (string) $id)->all();
    }

    public function toggle(int $id): void
    {
        $coupon = $this->find($id);
        $coupon->update(['is_active' => ! $coupon->is_active]);
    }

    public function delete(int $id): void
    {
        $this->find($id)->delete();
    }

    public function deleteMany(array $ids): void
    {
        Coupon::query()->whereIn('id', array_map('intval', $ids))->delete();
    }

    public function import(array $rows): int
    {
        return DB::transaction(function () use ($rows): int {
            foreach ($rows as $row) {
                Coupon::query()->updateOrCreate(['code' => strtoupper($row['code'])], [
                    'description' => $row['description'] ?? null,
                    'type' => $row['type'] ?? 'fixed',
                    'value' => $row['value'] ?? 0,
                    'min_order_value' => $row['min_order_value'] ?? 0,
                    'usage_limit' => $row['usage_limit'] ?? null,
                    'starts_at' => isset($row['starts_at']) ? Carbon::parse($row['starts_at']) : null,
                    'expires_at' => isset($row['expires_at']) ? Carbon::parse($row['expires_at']) : null,
                    'is_active' => $row['is_active'] ?? true,
                ]);
            }

            return count($rows);
        });
    }

    private function query(string $search)
    {
        return Coupon::query()->where('code', 'like', '%'.$search.'%')->orderByDesc('is_active')->orderByDesc('created_at');
    }
}
