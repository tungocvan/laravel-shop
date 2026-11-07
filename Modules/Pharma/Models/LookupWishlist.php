<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;

final class LookupWishlist extends Model
{
    protected $table = 'pharma_lookup_wishlists';

    protected $fillable = ['user_id', 'source_type', 'source_id', 'medicine_name',
        'registration_number', 'snapshot', 'search_context'];

    protected $casts = ['snapshot' => 'array', 'search_context' => 'array'];
}
