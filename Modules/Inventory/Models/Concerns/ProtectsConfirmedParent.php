<?php

namespace Modules\Inventory\Models\Concerns;

use Illuminate\Support\Facades\DB;
use LogicException;

trait ProtectsConfirmedParent
{
    protected static function bootProtectsConfirmedParent(): void
    {
        $guard = function ($model): void {
            $status = DB::table($model->confirmedParentTable())
                ->where('id', $model->getAttribute($model->confirmedParentForeignKey()))
                ->value('status');

            if ($status === 'CONFIRMED') {
                throw new LogicException('Lines of confirmed inventory documents are immutable.');
            }
        };

        static::updating($guard);
        static::deleting($guard);
    }

    abstract protected function confirmedParentTable(): string;

    abstract protected function confirmedParentForeignKey(): string;
}
