<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User; // Giả sử User model mặc định của Laravel

class Cart extends Model
{
    protected $fillable = ['session_id', 'user_id','coupon_id'];

    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class)->orderBy('id', 'asc');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
    public function coupon()
    {
        return $this->belongsTo(Coupon::class);
    }
}
