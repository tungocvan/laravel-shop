<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Request\Database\Factories\RequestRunFactory;
use Modules\Request\Domain\Enums\RunStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestRun extends Model
{
    use HasFactory, HasPublicUlid;

    protected $fillable = ['request_instance_id', 'sequence_number', 'request_type_version_id', 'request_payload_revision_id', 'status', 'current_stage_position', 'started_by', 'started_at', 'completed_at', 'terminal_reason', 'lock_version', 'activation_error_code', 'activation_failed_at', 'activation_retry_count', 'last_activation_correlation_id'];

    protected static function newFactory(): RequestRunFactory
    {
        return RequestRunFactory::new();
    }

    protected function casts(): array
    {
        return ['sequence_number' => 'integer', 'status' => RunStatus::class, 'current_stage_position' => 'integer', 'started_at' => 'immutable_datetime', 'completed_at' => 'immutable_datetime', 'lock_version' => 'integer', 'activation_failed_at' => 'immutable_datetime', 'activation_retry_count' => 'integer'];
    }

    public function requestInstance(): BelongsTo
    {
        return $this->belongsTo(InternalRequest::class, 'request_instance_id');
    }

    public function payloadRevision(): BelongsTo
    {
        return $this->belongsTo(RequestPayloadRevision::class, 'request_payload_revision_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(RequestTask::class, 'request_run_id');
    }
}
