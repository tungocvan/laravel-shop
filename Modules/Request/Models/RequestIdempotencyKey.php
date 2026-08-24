<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Request\Database\Factories\RequestIdempotencyKeyFactory;
use Modules\Request\Domain\Enums\IdempotencyStatus;

class RequestIdempotencyKey extends Model
{
    use HasFactory;

    protected static function newFactory(): RequestIdempotencyKeyFactory
    {
        return RequestIdempotencyKeyFactory::new();
    }

    protected $fillable = ['actor_id', 'command_key', 'aggregate_public_id', 'key_hash', 'request_fingerprint_hash', 'status', 'response_code', 'response_reference_json', 'correlation_id', 'locked_at', 'expires_at', 'completed_at'];

    protected function casts(): array
    {
        return ['actor_id' => 'integer', 'status' => IdempotencyStatus::class, 'response_code' => 'integer', 'response_reference_json' => 'array', 'locked_at' => 'immutable_datetime', 'expires_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime'];
    }
}
