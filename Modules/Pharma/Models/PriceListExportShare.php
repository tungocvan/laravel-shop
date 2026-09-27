<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

class PriceListExportShare extends Model
{
    protected $table = 'pharma_price_list_export_shares';
    protected $guarded = [];
    protected $casts = ['expires_at' => 'datetime', 'revoked_at' => 'datetime'];

    public function isAvailable(): bool
    {
        return $this->revoked_at === null && ($this->expires_at === null || $this->expires_at->isFuture());
    }
}
