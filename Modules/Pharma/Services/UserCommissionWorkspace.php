<?php

namespace Modules\Pharma\Services;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
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
    ): LengthAwarePaginator {
        $query=$this->commissions->userQuery($userId,[
            'from'=>$from,'to'=>$to,'source'=>$source,
        ])->with(['issue','medicine','partner']);

        $search=trim((string)$search);
        if($search!==''){
            $query->where(function($q) use($search): void {
                $like='%'.$search.'%';
                $q->whereHas('medicine',fn($m)=>$m->where('name','like',$like)->orWhere('medicine_code','like',$like))
                    ->orWhereHas('partner',fn($p)=>$p->where('name','like',$like))
                    ->orWhereHas('issue',fn($i)=>$i->where('number','like',$like)->orWhere('recipient_name','like',$like));
            });
        }

        return $query->orderByDesc('calculated_at')->orderByDesc('id')->paginate($perPage,['*'],'page',$page);
    }

    public function summary(int $userId,string $source='all',mixed $from=null,mixed $to=null): array
    {
        $base=$this->commissions->userQuery($userId,['from'=>$from,'to'=>$to,'source'=>$source]);
        $totals=(clone $base)->selectRaw('COALESCE(SUM(revenue_amount),0) revenue, COALESCE(SUM(commission_amount),0) commission')->first();

        return [
            'revenue'=>(float)($totals?->revenue ?? 0),
            'commission'=>(float)($totals?->commission ?? 0),
            'unresolved'=>(clone $base)->where('entry_type',InventoryIssueCommission::TYPE_EARNED)
                ->where('status',InventoryIssueCommission::STATUS_UNRESOLVED)->count(),
        ];
    }
}
