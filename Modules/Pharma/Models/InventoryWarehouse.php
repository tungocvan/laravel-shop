<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
class InventoryWarehouse extends Model {
 protected $table='pharma_inventory_warehouses'; protected $fillable=['code','name','is_active']; protected $casts=['is_active'=>'boolean'];
}