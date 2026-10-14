<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

final class PharmaQuickOrderRequest extends Model
{
    use SoftDeletes;

    protected $table = 'website_pharma_quick_order_requests';

    protected $guarded = [];

    protected $casts = [
        'requested_items' => 'array',
        'submitted_at' => 'datetime',
    ];

    public const PENDING_REVIEW = 'pending_review';
}
