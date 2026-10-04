<?php

namespace Modules\Pharma\Services;

use Illuminate\Database\Eloquent\Builder;
use Modules\Pharma\Models\InventoryIssueCommission;

final class CommissionQueryService
{
    public function adminQuery(array $filters=[]): Builder
    {
        return $this->applyFilters(InventoryIssueCommission::query()->where('entry_type',InventoryIssueCommission::TYPE_EARNED)->whereIn('status',[InventoryIssueCommission::STATUS_EARNED,InventoryIssueCommission::STATUS_UNRESOLVED]),$filters);
    }

    public function userQuery(int $userId,array $filters=[]): Builder
    {
        return $this->applyFilters(
            InventoryIssueCommission::query()->where('user_id',$userId)->where('entry_type',InventoryIssueCommission::TYPE_EARNED)->whereIn('status',[InventoryIssueCommission::STATUS_EARNED,InventoryIssueCommission::STATUS_UNRESOLVED]),
            $filters
        );
    }

    private function applyFilters(Builder $query,array $filters): Builder
    {
        return $query
            ->when(isset($filters['from'],$filters['to']),fn(Builder $q)=>$q->whereBetween('calculated_at',[$filters['from'],$filters['to']]))
            ->when(!empty($filters['source']) && $filters['source']!=='all',fn(Builder $q)=>$q->where('source_type',$filters['source']))
            ->when(!empty($filters['user_id']),fn(Builder $q)=>$q->where('user_id',(int)$filters['user_id']))
            ->when(!empty($filters['partner_id']),fn(Builder $q)=>$q->where('partner_id',(int)$filters['partner_id']))
            ->when(!empty($filters['medicine_id']),fn(Builder $q)=>$q->where('medicine_id',(int)$filters['medicine_id']))
            ->when(!empty($filters['ids']),fn(Builder $q)=>$q->whereIn('id',$filters['ids']));
    }
}
