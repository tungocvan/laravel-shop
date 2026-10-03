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
        ?string $search=null,
        string $source='all',
        mixed $from=null,
        mixed $to=null,
        int $perPage=20,
        int $page=1,
        bool $canViewTeam=false,
        ?int $managerUserId=null,
    ): LengthAwarePaginator {
        $query=$this->scopedQuery($userId,$canViewTeam,$managerUserId,$source,$from,$to);

        $search=trim((string)$search);
        if($search!==''){
            $query->where(function($q) use($search): void {
                $like='%'.$search.'%';
                $q->whereHas('medicine',fn($m)=>$m->where('name','like',$like)->orWhere('medicine_code','like',$like))
                    ->orWhereHas('partner',fn($p)=>$p->where('name','like',$like))
                    ->orWhereHas('issue',fn($i)=>$i->where('number','like',$like)->orWhere('recipient_name','like',$like));
            });
        }

        return $query
            ->selectRaw('issue_id, MAX(calculated_at) calculated_at, COALESCE(SUM(revenue_amount),0) revenue_amount, COALESCE(SUM(commission_amount),0) commission_amount')
            ->groupBy('issue_id')
            ->with(['issue.manager','issue.recipientPartner'])
            ->orderByDesc('calculated_at')
            ->orderByDesc('issue_id')
            ->paginate($perPage,['*'],'page',$page);
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
