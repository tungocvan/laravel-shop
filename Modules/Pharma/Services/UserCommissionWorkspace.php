<?php

namespace Modules\Pharma\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Modules\Pharma\Models\InventoryIssueCommission;

final class UserCommissionWorkspace
{
    public function __construct(private readonly CommissionQueryService $commissions) {}

    public function browse(
        int $userId,
        ?int $partnerId=null,
        string $source='all',
        mixed $from=null,
        mixed $to=null,
        int $perPage=20,
        int $page=1,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): LengthAwarePaginator {
        $query=$this->scopedQuery($userId,$canViewTeam,$managerUserId,$source,$from,$to);

        if($partnerId!==null){
            $query->where('partner_id',$partnerId);
        }

        return $query
            ->selectRaw('issue_id, MAX(calculated_at) calculated_at, COALESCE(SUM(revenue_amount),0) revenue_amount, COALESCE(SUM(commission_amount),0) commission_amount')
            ->groupBy('issue_id')
            ->with(['issue.manager','issue.recipientPartner'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('issue_id')
            ->paginate($perPage,['*'],'page',$page);
    }

    public function exportRows(
        int $userId,
        ?int $partnerId=null,
        string $source='all',
        mixed $from=null,
        mixed $to=null,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): Collection {
        return $this->scopedQuery($userId,$canViewTeam,$managerUserId,$source,$from,$to)
            ->when($partnerId!==null,fn(Builder $query)=>$query->where('partner_id',$partnerId))
            ->with(['issue','medicine','user','partner'])
            ->orderBy('calculated_at')
            ->orderBy('issue_id')
            ->orderBy('id')
            ->get();
    }

    public function detail(
        int $issueId,
        int $userId,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): ?array {
        $query=$this->scopedQuery($userId,$canViewTeam,$managerUserId,'all',null,null)
            ->where('issue_id',$issueId);

        if(!(clone $query)->exists()){
            return null;
        }

        $rows=$query
            ->with(['issue.manager','issue.recipientPartner','medicine','partner','user'])
            ->orderBy('calculated_at')
            ->orderBy('id')
            ->get();

        return [
            'issue'=>$rows->first()?->issue,
            'rows'=>$rows,
            'revenue'=>(float)$rows->sum(fn(InventoryIssueCommission $row)=>(float)$row->revenue_amount),
            'commission'=>(float)$rows->sum(fn(InventoryIssueCommission $row)=>(float)$row->commission_amount),
        ];
    }

    public function summary(
        int $userId,
        string $source='all',
        mixed $from=null,
        mixed $to=null,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): array {
        $base=$this->scopedQuery($userId,$canViewTeam,$managerUserId,$source,$from,$to);
        $totals=(clone $base)->selectRaw('COALESCE(SUM(revenue_amount),0) revenue, COALESCE(SUM(commission_amount),0) commission')->first();

        return [
            'revenue'=>(float)($totals?->revenue ?? 0),
            'commission'=>(float)($totals?->commission ?? 0),
            'unresolved'=>(clone $base)->where('entry_type',InventoryIssueCommission::TYPE_EARNED)
                ->where('status',InventoryIssueCommission::STATUS_UNRESOLVED)->count(),
        ];
    }

    public function commissionPartners(
        int $userId,
        string $source='all',
        mixed $from=null,
        mixed $to=null,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): Collection {
        return $this->scopedQuery($userId,$canViewTeam,$managerUserId,$source,$from,$to)
            ->whereNotNull('partner_id')
            ->with('partner:id,name')
            ->get(['partner_id'])
            ->pluck('partner')
            ->filter()
            ->unique('id')
            ->sortBy('name',SORT_NATURAL|SORT_FLAG_CASE)
            ->values();
    }

    public function commissionUsers(): Collection
    {
        $userIds=$this->commissions->adminQuery()
            ->whereNotNull('user_id')
            ->select('user_id')
            ->distinct()
            ->pluck('user_id');

        return User::query()
            ->whereIn('id',$userIds)
            ->orderBy('name')
            ->orderBy('id')
            ->get(['id','name','email']);
    }

    private function scopedQuery(
        int $userId,
        bool $canViewTeam,
        ?int $managerUserId,
        string $source,
        mixed $from,
        mixed $to,
    ): Builder {
        $filters=['from'=>$from,'to'=>$to,'source'=>$source];

        if(!$canViewTeam){
            return $this->commissions->userQuery($userId,$filters);
        }

        if($managerUserId!==null){
            $filters['user_id']=$managerUserId;
        }

        return $this->commissions->adminQuery($filters);
    }
}
