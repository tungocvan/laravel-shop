<?php

namespace Modules\Pharma\Services;

use Carbon\CarbonInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\InventoryWarehouse;
use Modules\Pharma\Models\InventoryBalance;

final class InventoryMovementSummaryService
{
    public function summarize(InventoryWarehouse $warehouse, CarbonInterface $from, CarbonInterface $to, ?int $medicineId=null, array $balanceIds=[], ?int $perPage=null): array
    {
        $before=DB::table('pharma_inventory_transactions')
            ->where('warehouse_id',$warehouse->id)->where('created_at','<',$from)
            ->selectRaw('medicine_id,batch_number,expiry_date,SUM(quantity_delta) as opening_quantity')
            ->groupBy('medicine_id','batch_number','expiry_date');

        $period=DB::table('pharma_inventory_transactions')
            ->where('warehouse_id',$warehouse->id)->whereBetween('created_at',[$from,$to])
            ->selectRaw("medicine_id,batch_number,expiry_date,
                SUM(CASE WHEN type='opening' THEN quantity_delta ELSE 0 END) as opening_import,
                SUM(CASE WHEN type='receipt' THEN quantity_delta WHEN type='receipt_reversal' THEN quantity_delta ELSE 0 END) as inbound_quantity,
                SUM(CASE WHEN type='issue' THEN ABS(quantity_delta) WHEN type='issue_reversal' THEN -quantity_delta ELSE 0 END) as outbound_quantity,
                SUM(quantity_delta) as net_quantity")
            ->groupBy('medicine_id','batch_number','expiry_date');

        $rows=DB::table('pharma_inventory_balances as b')
            ->join('pharma_medicines as m','m.id','=','b.medicine_id')
            ->leftJoinSub($before,'pre',fn($join)=>$join->on('pre.medicine_id','=','b.medicine_id')->on('pre.batch_number','=','b.batch_number')->on('pre.expiry_date','=','b.expiry_date'))
            ->leftJoinSub($period,'mov',fn($join)=>$join->on('mov.medicine_id','=','b.medicine_id')->on('mov.batch_number','=','b.batch_number')->on('mov.expiry_date','=','b.expiry_date'))
            ->where('b.warehouse_id',$warehouse->id)
            ->when($medicineId,fn($query)=>$query->where('b.medicine_id',$medicineId))
            ->when($balanceIds,fn($query)=>$query->whereIn('b.id',$balanceIds))
            ->select(['b.id','b.medicine_id','b.batch_number','b.expiry_date','m.medicine_code','m.name','m.unit'])
            ->selectRaw('COALESCE(pre.opening_quantity,0)+COALESCE(mov.opening_import,0) as period_opening')
            ->selectRaw('COALESCE(mov.opening_import,0) as opening_import')
            ->selectRaw('COALESCE(mov.inbound_quantity,0) as period_in')
            ->selectRaw('COALESCE(mov.outbound_quantity,0) as period_out')
            ->selectRaw('COALESCE(pre.opening_quantity,0)+COALESCE(mov.net_quantity,0) as period_closing')
            ->orderBy('m.name')->orderBy('b.expiry_date');

        $allRows=(clone $rows)->get();
        $rows=$perPage ? $rows->paginate($perPage)->withQueryString() : $allRows;
        $visibleRows=$rows instanceof LengthAwarePaginator ? $rows->getCollection() : $rows;

        $costs=$this->effectiveCosts($allRows);
        foreach($allRows as $row){
            $cost=$costs->get((int)$row->id);
            $row->effective_cost_price=$cost;
            $row->opening_value=$cost === null ? null : (float)$row->period_opening*$cost;
            $row->in_value=$cost === null ? null : (float)$row->period_in*$cost;
            $row->out_value=$cost === null ? null : (float)$row->period_out*$cost;
            $row->closing_value=$cost === null ? null : (float)$row->period_closing*$cost;
        }
        if($rows instanceof LengthAwarePaginator){
            $valuedById=$allRows->keyBy('id');
            $visibleRows->transform(fn($row)=>$valuedById->get($row->id,$row));
        }
        $unpriced=$allRows->filter(fn($row)=>$row->effective_cost_price === null || $row->effective_cost_price <= 0);

        return [
            'rows'=>$rows,
            'opening'=>(float)$allRows->sum('period_opening'),
            'opening_import'=>(float)$allRows->sum('opening_import'),
            'in'=>(float)$allRows->sum('period_in'),
            'out'=>(float)$allRows->sum('period_out'),
            'closing'=>(float)$allRows->sum('period_closing'),
            'opening_value'=>(float)$allRows->sum(fn($row)=>(float)($row->opening_value ?? 0)),
            'in_value'=>(float)$allRows->sum(fn($row)=>(float)($row->in_value ?? 0)),
            'out_value'=>(float)$allRows->sum(fn($row)=>(float)($row->out_value ?? 0)),
            'closing_value'=>(float)$allRows->sum(fn($row)=>(float)($row->closing_value ?? 0)),
            'unpriced_count'=>$unpriced->count(),
        ];
    }

    /**
     * The current ledger does not snapshot cost on each movement. Until it does,
     * movement valuation uses the same effective lot cost as the stock dashboard:
     * manual lot cost first, then active Supplier Tracking average.
     */
    private function effectiveCosts(Collection $rows): Collection
    {
        $balanceIds=$rows->pluck('id')->map(fn($id)=>(int)$id)->all();
        if($balanceIds === []) return collect();

        $balances=InventoryBalance::query()->whereIn('id',$balanceIds)->get(['id','medicine_id','manual_cost_price']);
        $medicineIds=$balances->pluck('medicine_id')->unique()->values()->all();
        $today=now()->toDateString();
        $supplierCosts=DB::table('pharma_supplier_trackings')
            ->whereIn('medicine_id',$medicineIds)
            ->where('status','active')
            ->where(fn($query)=>$query->whereNull('start_date')->orWhereDate('start_date','<=',$today))
            ->where(fn($query)=>$query->whereNull('end_date')->orWhereDate('end_date','>=',$today))
            ->whereNotNull('cost_price')
            ->groupBy('medicine_id')
            ->selectRaw('medicine_id, AVG(cost_price) as average_cost_price')
            ->pluck('average_cost_price','medicine_id');

        return $balances->mapWithKeys(function(InventoryBalance $balance)use($supplierCosts){
            $supplier=$supplierCosts->get($balance->medicine_id);
            $cost=$balance->manual_cost_price !== null
                ? (float)$balance->manual_cost_price
                : ($supplier !== null ? (float)$supplier : null);
            return [(int)$balance->id=>$cost];
        });
    }

}
