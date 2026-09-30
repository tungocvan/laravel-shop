<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Models\InventoryIssue;

final class UserInventoryIssueWorkspace
{
    public function browse(
        int $userId,
        ?string $search = null,
        ?string $status = null,
        ?string $source = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        int $perPage = 20,
        int $page = 1,
    ): LengthAwarePaginator {
        $search = trim((string) $search);

        return $this->visibleQuery($userId)
            ->with(['manager:id,name', 'priceList:id,code,name,type'])
            ->withCount('items')
            ->withSum('items as total_quantity', 'quantity')
            ->withSum('items as total_value', \DB::raw('quantity * unit_price'))
            ->when($search !== '', fn (Builder $query) => $query->where(function (Builder $nested) use ($search): void {
                $nested->where('number', 'like', "%{$search}%")
                    ->orWhere('recipient_name', 'like', "%{$search}%")
                    ->orWhere('bid_investor_name', 'like', "%{$search}%");
            }))
            ->when($status, fn (Builder $query) => $query->where('status', $status))
            ->when($source, fn (Builder $query) => $query->where('issue_source', $source))
            ->when($fromDate, fn (Builder $query) => $query->whereDate('issue_date', '>=', $fromDate))
            ->when($toDate, fn (Builder $query) => $query->whereDate('issue_date', '<=', $toDate))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(perPage: in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20, page: max(1, $page));
    }

    public function counts(int $userId): array
    {
        $query = $this->visibleQuery($userId);

        return [
            'all' => (clone $query)->count(),
            InventoryIssue::DRAFT => (clone $query)->where('status', InventoryIssue::DRAFT)->count(),
            InventoryIssue::PENDING_APPROVAL => (clone $query)->where('status', InventoryIssue::PENDING_APPROVAL)->count(),
            InventoryIssue::POSTED => (clone $query)->where('status', InventoryIssue::POSTED)->count(),
            InventoryIssue::CANCELLED => (clone $query)->where('status', InventoryIssue::CANCELLED)->count(),
        ];
    }

    public function findVisible(int $userId, int $issueId): ?InventoryIssue
    {
        return $this->visibleQuery($userId)
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items.medicine'])
            ->withCount('items')
            ->find($issueId);
    }

    public function findByCreator(int $creatorUserId, int $issueId): ?InventoryIssue
    {
        return InventoryIssue::query()
            ->where('created_by', $creatorUserId)
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items.medicine'])
            ->withCount('items')
            ->find($issueId);
    }

    private function visibleQuery(int $userId): Builder
    {
        return InventoryIssue::query()->where(function (Builder $query) use ($userId): void {
            $query->where('manager_user_id', $userId)->orWhere('created_by', $userId);
        });
    }
}
