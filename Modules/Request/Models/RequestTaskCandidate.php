<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Domain\Enums\CandidateSource;

class RequestTaskCandidate extends Model
{
    public $timestamps = false;

    protected $fillable = ['request_task_id', 'user_id', 'source_type', 'source_reference', 'user_snapshot_json', 'is_effective', 'created_at'];

    protected function casts(): array
    {
        return ['user_id' => 'integer', 'source_type' => CandidateSource::class, 'user_snapshot_json' => 'array', 'is_effective' => 'boolean', 'created_at' => 'immutable_datetime'];
    }

    public function task(): BelongsTo
    {
        return $this->belongsTo(RequestTask::class, 'request_task_id');
    }
}
