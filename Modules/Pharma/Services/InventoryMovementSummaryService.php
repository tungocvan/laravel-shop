<?php

namespace Modules\Pharma\Services;

use Carbon\CarbonInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Pharma\Models\InventoryWarehouse;

final class InventoryMovementSummaryService
{
    public function summarize(InventoryWarehouse $warehouse, CarbonInterface $from, CarbonInterface $to, ?int $medicineId=null, array $balanceIds=[]): array
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
            ->orderBy('m.name')->orderBy('b.expiry_date')->get();

        return [
            'rows'=>$rows,
            'opening'=>(float)$rows->sum('period_opening'),
            'opening_import'=>(float)$rows->sum('opening_import'),
            'in'=>(float)$rows->sum('period_in'),
            'out'=>(float)$rows->sum('period_out'),
            'closing'=>(float)$rows->sum('period_closing'),
        ];
    }
}
