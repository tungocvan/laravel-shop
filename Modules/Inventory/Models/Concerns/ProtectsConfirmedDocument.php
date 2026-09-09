<?php

namespace Modules\Inventory\Models\Concerns;

use LogicException;

trait ProtectsConfirmedDocument
{
    protected static function bootProtectsConfirmedDocument(): void
    {
        static::updating(function ($model): void {
            if (in_array($model->getOriginal('status'), ['CONFIRMED'], true)) {
                throw new LogicException('Confirmed inventory documents are immutable; use reversal or compensating movements.');
            }
        });

        static::deleting(function ($model): void {
            if (in_array($model->getOriginal('status'), ['CONFIRMED'], true)) {
                throw new LogicException('Confirmed inventory documents cannot be deleted.');
            }
        });
    }
}
