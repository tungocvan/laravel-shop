<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Modules\Request\Database\Factories\RequestTaskFactory;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Domain\Enums\TaskStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestTask extends Model
{
    use HasFactory, HasPublicUlid;

    protected $fillable = [
        'request_run_id',
        'request_stage_definition_id',
        'stage_key_snapshot',
        'stage_name_snapshot',
        'stage_position',
        'stage_mode',
        'status',
        'assignee_user_id',
        'resolver_key_snapshot',
        'resolver_source_snapshot_json',
        'sla_snapshot_json',
        'replacement_generation',
        'replaces_task_id',
        'replaced_by_task_id',
        'activated_at',
        'warning_at',
        'due_at',
        'grace_expires_at',
        'overdue_at',
        'suspended_at',
        'decided_at',
        'closed_at',
        'lock_version',
    ];

    protected static function newFactory(): RequestTaskFactory
    {
        return RequestTaskFactory::new();
    }

    protected function casts(): array
    {
        return [
            'stage_position' => 'integer',
            'stage_mode' => StageMode::class,
            'status' => TaskStatus::class,
            'assignee_user_id' => 'integer',
            'resolver_source_snapshot_json' => 'array',
            'sla_snapshot_json' => 'array',
            'replacement_generation' => 'integer',
            'activated_at' => 'immutable_datetime',
            'warning_at' => 'immutable_datetime',
            'due_at' => 'immutable_datetime',
            'grace_expires_at' => 'immutable_datetime',
            'overdue_at' => 'immutable_datetime',
            'suspended_at' => 'immutable_datetime',
            'decided_at' => 'immutable_datetime',
            'closed_at' => 'immutable_datetime',
            'lock_version' => 'integer',
        ];
    }

    public function run(): BelongsTo
    {
        return $this->belongsTo(RequestRun::class, 'request_run_id');
    }

    public function stageDefinition(): BelongsTo
    {
        return $this->belongsTo(RequestStageDefinition::class, 'request_stage_definition_id');
    }

    public function candidates(): HasMany
    {
        return $this->hasMany(RequestTaskCandidate::class, 'request_task_id');
    }

    public function decision(): HasOne
    {
        return $this->hasOne(RequestDecision::class, 'request_task_id');
    }
}
