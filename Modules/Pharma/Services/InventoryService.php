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
            $receipt=InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($receipt->status !== InventoryReceipt::DRAFT) throw ValidationException::withMessages(['status'=>'Chỉ phiếu nháp mới được ghi sổ.']);
            $receipt->load('items');
            if ($receipt->items->isEmpty()) throw ValidationException::withMessages(['items'=>'Phiếu nhập phải có ít nhất một dòng.']);
            foreach ($receipt->items as $item) $this->move($receipt->warehouse_id,$item->medicine_id,$item->batch_number,$item->expiry_date->toDateString(),(float)$item->quantity,'receipt',$receipt,$userId);
            $receipt->update(['status'=>InventoryReceipt::POSTED,'posted_by'=>$userId,'posted_at'=>now()]);
        });
    }

    public function revertReceipt(InventoryReceipt $receipt, ?int $userId): void
    {
        DB::transaction(function () use ($receipt,$userId): void {
            $receipt=InventoryReceipt::query()->lockForUpdate()->findOrFail($receipt->getKey());
            if ($receipt->status !== InventoryReceipt::POSTED) {
                throw ValidationException::withMessages(['status'=>'Chỉ phiếu đã ghi sổ mới được hoàn tác.']);
            }

            $postedMovements=InventoryTransaction::query()
                ->where('source_type',InventoryReceipt::class)
                ->where('source_id',$receipt->getKey())
                ->whereIn('type',['receipt','receipt_reversal'])
                ->selectRaw('warehouse_id, medicine_id, batch_number, expiry_date, SUM(quantity_delta) as quantity_delta')
                ->havingRaw('SUM(quantity_delta) > 0')
                ->groupBy('warehouse_id','medicine_id','batch_number','expiry_date')
                ->get();

            if ($postedMovements->isEmpty()) {
                throw ValidationException::withMessages(['stock'=>'Không tìm thấy bút toán nhập kho của phiếu để hoàn tác.']);
            }

            foreach ($postedMovements as $movement) {
                $expiry=$movement->expiry_date instanceof \DateTimeInterface
                    ? $movement->expiry_date->format('Y-m-d')
                    : (string)$movement->expiry_date;
                $balance=InventoryBalance::query()
                    ->where('warehouse_id',$movement->warehouse_id)
                    ->where('medicine_id',$movement->medicine_id)
                    ->where('batch_number',$movement->batch_number)
                    ->whereDate('expiry_date',$expiry)
                    ->lockForUpdate()->first();

                $quantity=(float)$movement->quantity_delta;
                if (! $balance || (float)$balance->quantity_on_hand < $quantity) {
                    throw ValidationException::withMessages([
                        'stock'=>"Không thể hoàn tác lô {$movement->batch_number}: tồn hiện tại không đủ để rút lại ".number_format($quantity,3,'.','').'.',
                    ]);
                }

                $after=(float)$balance->quantity_on_hand-$quantity;
                $balance->update(['quantity_on_hand'=>$after]);
                InventoryTransaction::create([
                    'warehouse_id'=>$movement->warehouse_id,'medicine_id'=>$movement->medicine_id,
                    'batch_number'=>$movement->batch_number,'expiry_date'=>$expiry,'type'=>'receipt_reversal',
                    'quantity_delta'=>-$quantity,'balance_after'=>$after,'source_type'=>InventoryReceipt::class,
                    'source_id'=>$receipt->getKey(),'created_by'=>$userId,
                    'notes'=>"Hoàn tác ghi sổ {$receipt->number}",
                ]);

                if ($after === 0.0 && (float)$balance->opening_quantity === 0.0) {
                    $balance->delete();
                }
            }

            $receipt->update(['status'=>InventoryReceipt::DRAFT,'posted_by'=>null,'posted_at'=>null]);
        });
    }

    public function postIssue(InventoryIssue $issue, ?int $userId): void
    {
        DB::transaction(function () use ($issue,$userId): void {
            $issue=InventoryIssue::query()->lockForUpdate()->findOrFail($issue->getKey());
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
        return InventoryBalance::query()->where('warehouse_id',$warehouseId)->where('medicine_id',$medicineId)->where('batch_number',$batch)->whereDate('expiry_date',$expiry)->lockForUpdate()->firstOrFail();
    }
}