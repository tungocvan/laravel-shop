<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestAuditEventFactory;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestAuditEvent extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestAuditEventFactory
    {
        return RequestAuditEventFactory::new();
    }

    public const UPDATED_AT = null;

    protected $fillable = ['request_instance_id', 'aggregate_type', 'aggregate_public_id', 'event_key', 'actor_user_id', 'effective_actor_user_id', 'context_json', 'reason', 'correlation_id', 'idempotency_key_hash', 'ip_address', 'user_agent', 'occurred_at'];

    protected function casts(): array
    {
        return ['context_json' => 'array', 'occurred_at' => 'immutable_datetime'];
    }

    public function requestInstance(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class, 'request_instance_id');
    }
}
