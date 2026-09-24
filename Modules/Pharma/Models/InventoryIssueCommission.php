<?php

namespace Modules\Pharma\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Partner\Models\Partner;

class InventoryIssueCommission extends Model
{
    public const TYPE_EARNED='earned';
    public const TYPE_REVERSAL='reversal';
    public const STATUS_EARNED='earned';
    public const STATUS_REVERSED='reversed';
    public const STATUS_UNRESOLVED='unresolved';

    protected $table='pharma_inventory_issue_commissions';
    protected $guarded=[];
    protected $casts=[
        'quantity'=>'decimal:3','unit_price'=>'decimal:4','revenue_amount'=>'decimal:2',
        'commission_percentage'=>'decimal:4','commission_amount'=>'decimal:2','calculated_at'=>'datetime',
    ];

    public function issue(): BelongsTo { return $this->belongsTo(InventoryIssue::class,'issue_id'); }
    public function issueItem(): BelongsTo { return $this->belongsTo(InventoryIssueItem::class,'issue_item_id'); }
    public function medicine(): BelongsTo { return $this->belongsTo(Medicine::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function partner(): BelongsTo { return $this->belongsTo(Partner::class); }
}
