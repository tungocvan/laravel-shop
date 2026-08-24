<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestNotificationDeliveryFactory;
use Modules\Request\Domain\Enums\NotificationDeliveryStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestNotificationDelivery extends Model
{
    use HasFactory, HasPublicUlid;

    protected $fillable = ['outbox_public_id', 'logical_key', 'channel', 'recipient_id', 'template_key', 'template_version', 'status', 'attempt_count', 'last_error_code', 'last_attempt_at', 'delivered_at'];

    protected static function newFactory(): RequestNotificationDeliveryFactory
    {
        return RequestNotificationDeliveryFactory::new();
    }

    protected function casts(): array
    {
        return ['recipient_id' => 'integer', 'template_version' => 'integer', 'status' => NotificationDeliveryStatus::class, 'attempt_count' => 'integer', 'last_attempt_at' => 'immutable_datetime', 'delivered_at' => 'immutable_datetime'];
    }

    public function outbox(): BelongsTo
    {
        return $this->belongsTo(RequestOutboxMessage::class, 'outbox_public_id', 'public_id');
    }
}
