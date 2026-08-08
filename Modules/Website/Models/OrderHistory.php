<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class OrderHistory extends Model
{
    protected $fillable = ['order_id', 'user_id', 'action', 'description'];

    // Quan hệ ngược về Order
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    // Quan hệ để biết Admin nào đã thao tác
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
