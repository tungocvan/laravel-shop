<?php
namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventoryIssueDeferredSupply extends Model
{
    public const PENDING='pending';
    protected $table='pharma_inventory_issue_deferred_supplies';
    protected $guarded=[];
    protected $casts=['quantity'=>'decimal:3','expected_supply_date'=>'date'];

    public function issue(): BelongsTo { return $this->belongsTo(InventoryIssue::class,'issue_id'); }
    public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
}
