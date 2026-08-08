<?php

namespace Modules\Chat\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class InternalMessage extends Model
{
    protected $fillable = [
        'from_id',
        'to_id',
        'message',
        'seen_at',
    ];

    protected $casts = [
        'seen_at' => 'datetime',
    ];

    public function fromUser()
    {
        return $this->belongsTo(User::class, 'from_id');
    }

    public function toUser()
    {
        return $this->belongsTo(User::class, 'to_id');
    }
}