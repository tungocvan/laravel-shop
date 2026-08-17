<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class ContractorManualLot extends Model
{
    protected $table = 'muasamcong_contractor_manual_lots';

    protected $guarded = [];

    protected $casts = [
        'quantity' => 'decimal:4',
        'price_plan' => 'decimal:4',
        'lot_price' => 'decimal:4',
        'plan_amount' => 'decimal:4',
        'raw_payload' => 'array',
        'confirmed_at' => 'datetime',
    ];
}
