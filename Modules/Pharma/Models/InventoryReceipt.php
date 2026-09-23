<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryReceipt extends Model {
 public const DRAFT='draft'; public const POSTED='posted'; public const CANCELLED='cancelled';
 protected $table='pharma_inventory_receipts'; protected $guarded=[]; protected $casts=['receipt_date'=>'date','invoice_date'=>'date','posted_at'=>'datetime'];
 public function items(): HasMany { return $this->hasMany(InventoryReceiptItem::class,'receipt_id'); }
}