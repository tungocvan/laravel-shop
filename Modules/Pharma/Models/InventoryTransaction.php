<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryTransaction extends Model {
 protected $table='pharma_inventory_transactions'; protected $guarded=[]; protected $casts=['expiry_date'=>'date','quantity_delta'=>'decimal:3','balance_after'=>'decimal:3'];
}