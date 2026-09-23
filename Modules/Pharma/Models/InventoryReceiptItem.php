<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryReceiptItem extends Model {
 protected $table='pharma_inventory_receipt_items'; protected $guarded=[]; protected $casts=['expiry_date'=>'date','quantity'=>'decimal:3','unit_price_ex_vat'=>'decimal:4','vat_rate'=>'decimal:2'];
 public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
}