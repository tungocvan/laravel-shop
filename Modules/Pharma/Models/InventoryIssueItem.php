<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryIssueItem extends Model {
 protected $table='pharma_inventory_issue_items'; protected $guarded=[]; protected $casts=['expiry_date'=>'date','quantity'=>'decimal:3','unit_price'=>'decimal:4'];
 public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
}