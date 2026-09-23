<?php
namespace Modules\Pharma\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Modules\Pharma\Models\InventoryBalance;
use Modules\Pharma\Models\InventoryIssue;
use Modules\Pharma\Models\InventoryReceipt;
use Modules\Pharma\Models\InventoryTransaction;
use Modules\Pharma\Models\InventoryWarehouse;

final class InventoryService
{
    public function defaultWarehouse(): InventoryWarehouse
    {
        return InventoryWarehouse::query()->firstOrCreate(['code'=>'MAIN'], ['name'=>'Kho chính','is_active'=>true]);
    }

    public function postReceipt(InventoryReceipt $receipt, ?int $userId): void
    {
        DB::transaction(function () use ($receipt,$userId): void {
            $receipt->lockForUpdate()->first();
            if ($receipt->status !== InventoryReceipt::DRAFT) throw ValidationException::withMessages(['status'=>'Chỉ phiếu nháp mới được ghi sổ.']);
            $receipt->load('items');
            if ($receipt->items->isEmpty()) throw ValidationException::withMessages(['items'=>'Phiếu nhập phải có ít nhất một dòng.']);
            foreach ($receipt->items as $item) $this->move($receipt->warehouse_id,$item->medicine_id,$item->batch_number,$item->expiry_date->toDateString(),(float)$item->quantity,'receipt',$receipt,$userId);
            $receipt->update(['status'=>InventoryReceipt::POSTED,'posted_by'=>$userId,'posted_at'=>now()]);
        });
    }

    public function postIssue(InventoryIssue $issue, ?int $userId): void
    {
        DB::transaction(function () use ($issue,$userId): void {
            $issue->lockForUpdate()->first();
            if ($issue->status !== InventoryIssue::DRAFT) throw ValidationException::withMessages(['status'=>'Chỉ phiếu nháp mới được ghi sổ.']);
            $issue->load('items');
            if ($issue->items->isEmpty()) throw ValidationException::withMessages(['items'=>'Phiếu xuất phải có ít nhất một dòng.']);
            foreach ($issue->items as $item) $this->move($issue->warehouse_id,$item->medicine_id,$item->batch_number,$item->expiry_date->toDateString(),-(float)$item->quantity,'issue',$issue,$userId);
            $issue->update(['status'=>InventoryIssue::POSTED,'posted_by'=>$userId,'posted_at'=>now()]);
        });
    }

    public function setOpeningBalance(int $warehouseId,int $medicineId,string $batch,string $expiry,float $quantity,?int $userId): void
    {
        DB::transaction(function () use ($warehouseId,$medicineId,$batch,$expiry,$quantity,$userId): void {
            $balance=$this->lockedBalance($warehouseId,$medicineId,$batch,$expiry);
            if ((float)$balance->opening_quantity !== 0.0 || (float)$balance->quantity_on_hand !== 0.0) throw ValidationException::withMessages(['quantity'=>'Lô đã phát sinh tồn, không thể nhập tồn đầu kỳ lần nữa.']);
            $balance->update(['opening_quantity'=>$quantity,'quantity_on_hand'=>$quantity]);
            InventoryTransaction::create(['warehouse_id'=>$warehouseId,'medicine_id'=>$medicineId,'batch_number'=>$batch,'expiry_date'=>$expiry,'type'=>'opening','quantity_delta'=>$quantity,'balance_after'=>$quantity,'created_by'=>$userId]);
        });
    }

    private function move(int $warehouseId,int $medicineId,string $batch,string $expiry,float $delta,string $type,object $source,?int $userId): void
    {
        $balance=$this->lockedBalance($warehouseId,$medicineId,$batch,$expiry);
        $after=(float)$balance->quantity_on_hand+$delta;
        if ($after < 0) throw ValidationException::withMessages(['stock'=>"Không đủ tồn cho lô {$batch}."]);
        $balance->update(['quantity_on_hand'=>$after]);
        InventoryTransaction::create(['warehouse_id'=>$warehouseId,'medicine_id'=>$medicineId,'batch_number'=>$batch,'expiry_date'=>$expiry,'type'=>$type,'quantity_delta'=>$delta,'balance_after'=>$after,'source_type'=>$source::class,'source_id'=>$source->getKey(),'created_by'=>$userId]);
    }

    private function lockedBalance(int $warehouseId,int $medicineId,string $batch,string $expiry): InventoryBalance
    {
        InventoryBalance::query()->firstOrCreate(['warehouse_id'=>$warehouseId,'medicine_id'=>$medicineId,'batch_number'=>$batch,'expiry_date'=>$expiry],['opening_quantity'=>0,'quantity_on_hand'=>0]);
        return InventoryBalance::query()->where(compact('warehouseId'))->where('warehouse_id',$warehouseId)->where('medicine_id',$medicineId)->where('batch_number',$batch)->whereDate('expiry_date',$expiry)->lockForUpdate()->firstOrFail();
    }
}