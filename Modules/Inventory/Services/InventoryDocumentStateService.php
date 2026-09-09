<?php

namespace Modules\Inventory\Services;

use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Issue;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\Stocktake;
use Modules\Inventory\Models\Transfer;

class InventoryDocumentStateService
{
    public function cancelReceipt(int $id, ?int $actorId = null): Receipt
    {
        return $this->cancel(Receipt::class, $id, $actorId);
    }

    public function cancelIssue(int $id, ?int $actorId = null): Issue
    {
        return $this->cancel(Issue::class, $id, $actorId);
    }

    public function cancelTransfer(int $id, ?int $actorId = null): Transfer
    {
        return $this->cancel(Transfer::class, $id, $actorId);
    }

    public function markStocktakeCounted(int $id, ?int $actorId = null): Stocktake
    {
        return DB::transaction(function () use ($id, $actorId): Stocktake {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($id);

            if ($stocktake->status === 'COUNTED') {
                return $stocktake;
            }

            if ($stocktake->status !== 'DRAFT') {
                throw new DomainException('Only draft stocktakes can move to COUNTED.');
            }

            $stocktake->status = 'COUNTED';
            $stocktake->counted_by = $actorId;
            $stocktake->counted_at = now();
            $stocktake->save();

            return $stocktake->refresh();
        }, 3);
    }

    public function cancelStocktake(int $id, ?int $actorId = null): Stocktake
    {
        return DB::transaction(function () use ($id, $actorId): Stocktake {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($id);

            if ($stocktake->status === 'CANCELLED') {
                return $stocktake;
            }

            if (! in_array($stocktake->status, ['DRAFT', 'COUNTED'], true)) {
                throw new DomainException('Confirmed stocktakes cannot be cancelled directly.');
            }

            $stocktake->status = 'CANCELLED';
            $stocktake->cancelled_by = $actorId;
            $stocktake->cancelled_at = now();
            $stocktake->save();

            return $stocktake->refresh();
        }, 3);
    }

    /** @template T of Model */
    private function cancel(string $modelClass, int $id, ?int $actorId): Model
    {
        return DB::transaction(function () use ($modelClass, $id, $actorId): Model {
            /** @var Model&object{status:string} $document */
            $document = $modelClass::query()->lockForUpdate()->findOrFail($id);

            if ($document->status === 'CANCELLED') {
                return $document;
            }

            if ($document->status !== 'DRAFT') {
                throw new DomainException('Confirmed inventory documents cannot be cancelled directly.');
            }

            $document->status = 'CANCELLED';
            $document->cancelled_by = $actorId;
            $document->cancelled_at = now();
            $document->save();

            return $document->refresh();
        }, 3);
    }
}
