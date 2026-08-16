<?php

namespace Modules\Muasamcong\Models;

use Illuminate\Database\Eloquent\Model;

class PricingWishlist extends Model
{
    protected $table = 'muasamcong_pricing_wishlists';

    protected $guarded = [];

    protected $casts = [
        'snapshot' => 'array',
    ];
}
