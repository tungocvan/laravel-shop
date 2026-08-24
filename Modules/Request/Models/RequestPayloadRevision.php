<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Request\Database\Factories\RequestPayloadRevisionFactory;
use Modules\Request\Domain\Enums\PayloadSource;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestPayloadRevision extends Model
{
    use HasFactory, HasPublicUlid;

    public const UPDATED_AT = null;

    protected $fillable = ['request_instance_id', 'revision_number', 'request_type_version_id', 'payload_json', 'display_snapshot_json', 'payload_checksum', 'schema_version', 'source', 'created_by'];

    protected static function newFactory(): RequestPayloadRevisionFactory
    {
        return RequestPayloadRevisionFactory::new();
    }

    protected static function booted(): void
    {
        static::updating(fn (): never => throw new LogicException('Request payload revisions are immutable.'));
        static::deleting(fn (): never => throw new LogicException('Request payload revisions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return ['revision_number' => 'integer', 'payload_json' => 'array', 'display_snapshot_json' => 'array', 'schema_version' => 'integer', 'source' => PayloadSource::class, 'created_at' => 'immutable_datetime'];
    }

    public function requestInstance(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class, 'request_instance_id');
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'request_type_version_id');
    }
}
