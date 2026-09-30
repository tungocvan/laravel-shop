<?php
namespace Modules\Pharma\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
class InventoryIssue extends Model {
 public const DRAFT='draft'; public const PENDING_APPROVAL='pending_approval'; public const POSTED='posted'; public const CANCELLED='cancelled';
 protected $table='pharma_inventory_issues'; protected $guarded=[]; protected $casts=['issue_date'=>'date','submitted_at'=>'datetime','posted_at'=>'datetime'];
 public function items(): HasMany { return $this->hasMany(InventoryIssueItem::class,'issue_id'); }
 public function deferredSupplies(): HasMany { return $this->hasMany(InventoryIssueDeferredSupply::class,'issue_id'); }
 public function priceList(): BelongsTo { return $this->belongsTo(PriceList::class,'price_list_id'); }
 public function manager(): BelongsTo { return $this->belongsTo(\App\Models\User::class,'manager_user_id'); }
 public function recipientPartner(): BelongsTo { return $this->belongsTo(\Modules\Partner\Models\Partner::class,'recipient_partner_id'); }
}