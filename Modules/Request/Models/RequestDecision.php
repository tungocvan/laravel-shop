<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;
use Modules\Request\Domain\Enums\DecisionType;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestDecision extends Model
{
    use HasPublicUlid;

    public $timestamps = false;

    protected $fillable = ['request_task_id', 'request_run_id', 'request_instance_id', 'decision', 'actor_user_id', 'effective_actor_user_id', 'reason', 'context_snapshot_json', 'idempotency_key_hash', 'correlation_id', 'decided_at', 'created_at'];

    protected static function booted(): void
    {
        static::updating(fn () => throw new LogicException('Request decisions are immutable.'));
        static::deleting(fn () => throw new LogicException('Request decisions cannot be deleted.'));
    }

    protected function casts(): array
    {
        return ['decision' => DecisionType::class, 'actor_user_id' => 'integer', 'effective_actor_user_id' => 'integer', 'context_snapshot_json' => 'array', 'decided_at' => 'immutable_datetime', 'created_at' => 'immutable_datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(RequestTask::class, 'request_task_id');
    }
}
