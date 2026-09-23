<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
class InventoryBalance extends Model {
 protected $table='pharma_inventory_balances'; protected $fillable=['warehouse_id','medicine_id','batch_number','expiry_date','opening_quantity','quantity_on_hand'];
 protected $casts=['expiry_date'=>'date','opening_quantity'=>'decimal:3','quantity_on_hand'=>'decimal:3'];
 public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
 public function warehouse(): BelongsTo { return $this->belongsTo(InventoryWarehouse::class,'warehouse_id'); }
}