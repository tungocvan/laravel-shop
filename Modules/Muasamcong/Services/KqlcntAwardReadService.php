<?php

namespace Modules\Muasamcong\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Modules\Muasamcong\Models\KqlcntAwardItem;

final class KqlcntAwardReadService
{
    public function paginate(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')
            ->when($filters['notify_no'] ?? null, fn ($q, $v) => $q->where('notify_no', 'like', '%'.$v.'%'))
            ->when($filters['contractor'] ?? null, fn ($q, $v) => $q->where('contractor_name', 'like', '%'.$v.'%'))
            ->when($filters['medicine'] ?? null, fn ($q, $v) => $q->where('medicine_name', 'like', '%'.$v.'%'))
            ->when(isset($filters['active']), fn ($q) => $q->where('is_active', $filters['active'] === '1'))
            ->orderByDesc('id')->paginate($perPage)->withQueryString();
    }

    public function find(int $id): KqlcntAwardItem
    {
        return KqlcntAwardItem::query()->whereNotNull('synced_from_catalog_at')->findOrFail($id);
    }
}
