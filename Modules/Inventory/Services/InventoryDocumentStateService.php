<?php

namespace Modules\Inventory\Services;

use App\Models\User;
use DomainException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Modules\Inventory\Models\Issue;
use Modules\Inventory\Models\Receipt;
use Modules\Inventory\Models\Stocktake;
use Modules\Inventory\Models\Transfer;

class InventoryDocumentStateService
{
    public function __construct(private readonly InventoryAuthorizationService $authorization) {}

    public function cancelReceipt(int $id, User $actor): Receipt
    {
        $this->authorization->authorize($actor, 'inventory.receipt.manage');

        return $this->cancel(Receipt::class, $id, $actor);
    }

    public function cancelIssue(int $id, User $actor): Issue
    {
        $this->authorization->authorize($actor, 'inventory.issue.manage');

        return $this->cancel(Issue::class, $id, $actor);
    }

    public function cancelTransfer(int $id, User $actor): Transfer
    {
        $this->authorization->authorize($actor, 'inventory.transfer.manage');

        return $this->cancel(Transfer::class, $id, $actor);
    }

    public function markStocktakeCounted(int $id, User $actor): Stocktake
    {
        $this->authorization->authorize($actor, 'inventory.stocktake.manage');

        return DB::transaction(function () use ($id, $actor): Stocktake {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($id);

            if ($stocktake->status === 'COUNTED') {
                return $stocktake;
            }

            if ($stocktake->status !== 'DRAFT') {
                throw new DomainException('Only draft stocktakes can move to COUNTED.');
            }

            $stocktake->status = 'COUNTED';
            $stocktake->counted_by = $actor->getKey();
            $stocktake->counted_at = now();
            $stocktake->save();

            return $stocktake->refresh();
        }, 3);
    }

    public function cancelStocktake(int $id, User $actor): Stocktake
    {
        $this->authorization->authorize($actor, 'inventory.stocktake.manage');

        return DB::transaction(function () use ($id, $actor): Stocktake {
            $stocktake = Stocktake::query()->lockForUpdate()->findOrFail($id);

            if ($stocktake->status === 'CANCELLED') {
                return $stocktake;
            }

            if (! in_array($stocktake->status, ['DRAFT', 'COUNTED'], true)) {
                throw new DomainException('Confirmed stocktakes cannot be cancelled directly.');
            }

            $stocktake->status = 'CANCELLED';
            $stocktake->cancelled_by = $actor->getKey();
            $stocktake->cancelled_at = now();
            $stocktake->save();

            return $stocktake->refresh();
        }, 3);
    }

    private function cancel(string $modelClass, int $id, User $actor): Model
    {
        return DB::transaction(function () use ($modelClass, $id, $actor): Model {
            $document = $modelClass::query()->lockForUpdate()->findOrFail($id);

            if ($document->status === 'CANCELLED') {
                return $document;
            }

            if ($document->status !== 'DRAFT') {
                throw new DomainException('Confirmed inventory documents cannot be cancelled directly.');
            }

            $document->status = 'CANCELLED';
            $document->cancelled_by = $actor->getKey();
            $document->cancelled_at = now();
            $document->save();

            return $document->refresh();
        }, 3);
    }
}
