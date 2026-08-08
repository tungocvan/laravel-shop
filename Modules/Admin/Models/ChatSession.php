<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use App\Models\User;

class ChatSession extends Model
{
    protected $fillable = [
        'session_token', 'user_id', 'admin_id',
        'guest_name', 'guest_phone', 'guest_email',
        'status', 'last_message_at'
    ];

    protected $casts = [
        'last_message_at' => 'datetime',
    ];

    // Lấy tên hiển thị của đối tượng chat
    public function getDisplayNameAttribute(): string
    {
        if ($this->user_id) return $this->user->name;
        return $this->guest_name ?? "Khách #" . $this->id;
    }

    public function messages(): HasMany
    {
        return $this->hasMany(ChatMessage::class);
    }

    public function latestMessage()
    {
        return $this->hasOne(ChatMessage::class)->latestOfMany();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function admin(): BelongsTo
    {
        return $this->belongsTo(User::class, 'admin_id');
    }
}
