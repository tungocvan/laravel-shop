<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Models\PriceList;

final class UserPriceListWorkspace
{
    public function browse(
        int $userId,
        ?string $search = null,
        ?string $status = null,
        int $perPage = 25,
        int $page = 1,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $managerUserId = null,
        bool $approverScope = false,
    ): LengthAwarePaginator {
        $search = trim((string) $search);

        $query = $approverScope ? PriceList::query() : $this->managedQuery($userId);

        return $query
            ->with(['partner', 'officialFacility', 'purpose', 'manager'])
            ->withCount('items')
            ->when($search !== '', function (Builder $query) use ($search): void {
                $query->where(function (Builder $inner) use ($search): void {
                    $inner->where('code', 'like', "%{$search}%")
                        ->orWhere('name', 'like', "%{$search}%")
                        ->orWhereHas('partner', fn (Builder $partner) => $partner->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('officialFacility', fn (Builder $facility) => $facility->where('facility_name', 'like', "%{$search}%"));
                });
            })
            ->when($managerUserId, fn (Builder $query) => $query->where('manager_user_id', $managerUserId))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($fromDate, fn (Builder $query) => $query->whereDate('effective_from', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->whereDate('effective_from', '<=', $toDate))
            ->orderByDesc('updated_at')
            ->orderByDesc('id')
            ->paginate(perPage: $perPage, page: $page);
    }

    public function counts(int $userId, ?int $managerUserId = null, bool $approverScope = false): array
    {
        $query = $approverScope ? PriceList::query() : $this->managedQuery($userId);
        $query->when($managerUserId, fn (Builder $builder) => $builder->where('manager_user_id', $managerUserId));

        return [
            'all' => (clone $query)->count(),
            PriceList::STATUS_DRAFT => (clone $query)->where('status', PriceList::STATUS_DRAFT)->count(),
            PriceList::STATUS_PENDING_APPROVAL => (clone $query)->where('status', PriceList::STATUS_PENDING_APPROVAL)->count(),
            PriceList::STATUS_REJECTED => (clone $query)->where('status', PriceList::STATUS_REJECTED)->count(),
            PriceList::STATUS_ACTIVE => (clone $query)->where('status', PriceList::STATUS_ACTIVE)->count(),
            PriceList::STATUS_INACTIVE => (clone $query)->where('status', PriceList::STATUS_INACTIVE)->count(),
            PriceList::STATUS_ARCHIVED => (clone $query)->where('status', PriceList::STATUS_ARCHIVED)->count(),
        ];
    }

    public function findManaged(int $userId, int $priceListId): ?PriceList
    {
        return $this->managedQuery($userId)
            ->with([
                'partner',
                'officialFacility',
                'purpose',
                'items.variant.medicine',
                'items.package',
            ])
            ->withCount('items')
            ->find($priceListId);
    }

    private function managedQuery(int $userId): Builder
    {
        return PriceList::query()->where('manager_user_id', $userId);
    }
}
