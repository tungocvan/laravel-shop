<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

final class AwardWishlist extends Model
{
    protected $table = 'pharma_award_wishlists';

    protected $fillable = [
        'user_id', 'source_id', 'search_keyword', 'source_page',
        'medicine_name', 'medicine_group', 'snapshot',
    ];

    protected $casts = [
        'snapshot' => 'array',
        'source_page' => 'integer',
    ];
}
