<?php

namespace Modules\Request\Models\Concerns;

use Illuminate\Support\Str;

trait HasPublicUlid
{
    protected static function bootHasPublicUlid(): void
    {
        static::creating(function (self $model): void {
            if ($model->getAttribute('public_id') === null) {
                $model->setAttribute('public_id', (string) Str::ulid());
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }
}
