<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Request\Database\Factories\InternalRequestFactory;
use Modules\Request\Domain\Enums\RequestStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class InternalRequest extends Model
{
    use HasFactory, HasPublicUlid;

    protected $table = 'request_instances';

    protected $fillable = ['request_number', 'request_type_id', 'request_type_version_id', 'requester_id', 'status', 'title_snapshot', 'requester_snapshot_json', 'current_payload_revision_id', 'current_run_id', 'lock_version', 'submitted_at', 'approved_at', 'rejected_at', 'returned_at', 'cancelled_at', 'archived_at', 'cancelled_by', 'cancellation_reason'];

    protected static function newFactory(): InternalRequestFactory
    {
        return InternalRequestFactory::new();
    }

    protected function casts(): array
    {
        return ['requester_id' => 'integer', 'status' => RequestStatus::class, 'requester_snapshot_json' => 'array', 'lock_version' => 'integer', 'submitted_at' => 'immutable_datetime', 'approved_at' => 'immutable_datetime', 'rejected_at' => 'immutable_datetime', 'returned_at' => 'immutable_datetime', 'cancelled_at' => 'immutable_datetime', 'archived_at' => 'immutable_datetime'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RequestType::class, 'request_type_id');
    }

    public function typeVersion(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'request_type_version_id');
    }

    public function payloadRevisions(): HasMany
    {
        return $this->hasMany(RequestPayloadRevision::class, 'request_instance_id');
    }

    public function latestPayloadRevision(): HasOne
    {
        return $this->hasOne(RequestPayloadRevision::class, 'request_instance_id')->ofMany('revision_number', 'max');
    }

    public function runs(): HasMany
    {
        return $this->hasMany(RequestRun::class, 'request_instance_id');
    }

    public function currentPayloadRevision(): BelongsTo
    {
        return $this->belongsTo(RequestPayloadRevision::class, 'current_payload_revision_id');
    }

    public function currentRun(): BelongsTo
    {
        return $this->belongsTo(RequestRun::class, 'current_run_id');
    }
}
