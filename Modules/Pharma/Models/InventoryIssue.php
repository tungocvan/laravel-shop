<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryIssue extends Model {
 public const DRAFT='draft'; public const POSTED='posted'; public const CANCELLED='cancelled';
 protected $table='pharma_inventory_issues'; protected $guarded=[]; protected $casts=['issue_date'=>'date','posted_at'=>'datetime'];
 public function items(): HasMany { return $this->hasMany(InventoryIssueItem::class,'issue_id'); }
}