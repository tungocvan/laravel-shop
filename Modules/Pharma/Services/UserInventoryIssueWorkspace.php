<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use App\Models\User;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryTransaction;

final class UserInventoryIssueWorkspace
{
    public function browse(
        int $userId,
        ?string $search = null,
        ?string $status = null,
        ?string $source = null,
        ?string $fromDate = null,
        ?string $toDate = null,
        ?int $managerUserId = null,
        int $perPage = 20,
        int $page = 1,
        bool $includeApprovalScope = false,
    ): LengthAwarePaginator {
        $search = trim((string) $search);

        $paginator = $this->visibleQuery($userId, $includeApprovalScope)
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items:id,issue_id,medicine_id,quantity,unit_price'])
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
            ->when($managerUserId, fn (Builder $query) => $query->where('manager_user_id', $managerUserId))
            ->orderByDesc('issue_date')
            ->orderByDesc('id')
            ->paginate(perPage: in_array($perPage, [20, 25, 50, 100], true) ? $perPage : 20, page: max(1, $page));

        $postedIds=$paginator->getCollection()->where('status',InventoryIssue::POSTED)->pluck('id');
        $postedMedicineIds=InventoryTransaction::query()
            ->whereIn('source_id',$postedIds)
            ->where('source_type',InventoryIssue::class)
            ->where('type','issue')
            ->where('quantity_delta','<',0)
            ->get(['source_id','medicine_id'])
            ->groupBy('source_id')
            ->map(fn($rows)=>$rows->pluck('medicine_id')->map(fn($id)=>(int)$id)->unique());

        $paginator->getCollection()->each(function(InventoryIssue $issue)use($postedMedicineIds): void {
            if($issue->status!==InventoryIssue::POSTED) return;
            $medicineIds=$postedMedicineIds->get($issue->id,collect());
            $postedItems=$issue->items->filter(fn($item)=>$medicineIds->contains((int)$item->medicine_id));
            $issue->items_count=$postedItems->count();
            $issue->total_quantity=(float)$postedItems->sum('quantity');
            $issue->total_value=(float)$postedItems->sum(fn($item)=>(float)$item->quantity*(float)$item->unit_price);
        });

        return $paginator;
    }

    public function counts(int $userId, bool $includeApprovalScope = false, ?int $managerUserId = null): array
    {
        $query = $this->visibleQuery($userId, $includeApprovalScope)
            ->when($managerUserId, fn (Builder $builder) => $builder->where('manager_user_id', $managerUserId));

        return [
            'all' => (clone $query)->count(),
            InventoryIssue::DRAFT => (clone $query)->where('status', InventoryIssue::DRAFT)->count(),
            InventoryIssue::PENDING_APPROVAL => (clone $query)->where('status', InventoryIssue::PENDING_APPROVAL)->count(),
            InventoryIssue::APPROVED => (clone $query)->where('status', InventoryIssue::APPROVED)->count(),
            InventoryIssue::REJECTED => (clone $query)->where('status', InventoryIssue::REJECTED)->count(),
            InventoryIssue::POSTED => (clone $query)->where('status', InventoryIssue::POSTED)->count(),
            InventoryIssue::CANCELLED => (clone $query)->where('status', InventoryIssue::CANCELLED)->count(),
        ];
    }

    public function managerOptions(int $userId, bool $includeApprovalScope = false)
    {
        $managerIds = $this->visibleQuery($userId, $includeApprovalScope)
            ->whereNotNull('manager_user_id')
            ->distinct()
            ->pluck('manager_user_id');

        return User::query()
            ->whereIn('id', $managerIds)
            ->orderBy('name')
            ->get(['id', 'name']);
    }

    public function findVisible(int $userId, int $issueId): ?InventoryIssue
    {
        return $this->visibleQuery($userId)
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items.medicine'])
            ->withCount('items')
            ->find($issueId);
    }

    public function findPendingForApproval(int $issueId): ?InventoryIssue
    {
        return InventoryIssue::query()
            ->where('status', InventoryIssue::PENDING_APPROVAL)
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items.medicine'])
            ->withCount('items')
            ->find($issueId);
    }

    public function findApprovedForUndo(int $issueId): ?InventoryIssue
    {
        return InventoryIssue::query()
            ->where('status', InventoryIssue::APPROVED)
            ->whereNull('posted_at')
            ->with(['manager:id,name', 'priceList:id,code,name,type', 'items.medicine'])
            ->withCount('items')
            ->find($issueId);
    }

    public function findDeletableForApproval(int $issueId): ?InventoryIssue
    {
        return InventoryIssue::query()
            ->whereIn('status', [InventoryIssue::DRAFT, InventoryIssue::REJECTED])
            ->whereNull('posted_at')
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

    private function visibleQuery(int $userId, bool $includeApprovalScope = false): Builder
    {
        $query = InventoryIssue::query();

        if ($includeApprovalScope) {
            return $query;
        }

        return $query->where(function (Builder $query) use ($userId): void {
            $query->where('manager_user_id', $userId)->orWhere('created_by', $userId);
        });
    }
}
