<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryWarehouse extends Model {
 protected $table='pharma_inventory_warehouses';
 protected $fillable=['code','name','is_active','opening_cutoff_at'];
 protected $casts=['is_active'=>'boolean','opening_cutoff_at'=>'datetime'];
}
