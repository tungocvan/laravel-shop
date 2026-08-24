<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Request\Database\Factories\RequestOutboxMessageFactory;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestOutboxMessage extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestOutboxMessageFactory
    {
        return RequestOutboxMessageFactory::new();
    }

    protected $fillable = ['event_key', 'aggregate_type', 'aggregate_public_id', 'payload_json', 'correlation_id', 'available_at', 'attempt_count', 'last_error_code', 'last_error_at', 'dispatched_at', 'failed_at'];

    protected function casts(): array
    {
        return ['payload_json' => 'array', 'available_at' => 'immutable_datetime', 'attempt_count' => 'integer', 'last_error_at' => 'immutable_datetime', 'dispatched_at' => 'immutable_datetime', 'failed_at' => 'immutable_datetime'];
    }
}
