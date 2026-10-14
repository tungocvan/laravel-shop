<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Pharma\Models\InventoryIssue;

final class PharmaQuickOrderRequest extends Model
{
    use SoftDeletes;

    protected $table = 'website_pharma_quick_order_requests';

    protected $guarded = [];

    protected $casts = [
        'requested_items' => 'array',
        'submitted_at' => 'datetime',
    ];

    public function inventoryIssue(): BelongsTo
    {
        return $this->belongsTo(InventoryIssue::class, 'inventory_issue_id');
    }

    public function getCustomerOrderStatusAttribute(): string
    {
        if ($this->status === 'rejected') {
            return 'rejected';
        }
        if ($this->status !== 'accepted') {
            return 'pending_review';
        }
        if ($this->inventory_issue_id && $this->inventoryIssue?->status === InventoryIssue::REJECTED) {
            return 'issue_rejected';
        }
        if ($this->inventory_issue_id && $this->inventoryIssue?->status === InventoryIssue::CANCELLED) {
            return 'issue_cancelled';
        }
        if ($this->inventory_issue_id && $this->inventoryIssue?->status === InventoryIssue::POSTED) {
            return 'completed';
        }

        return 'processing';
    }

    public const PENDING_REVIEW = 'pending_review';
}
