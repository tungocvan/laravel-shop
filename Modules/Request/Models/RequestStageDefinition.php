<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestStageDefinitionFactory;
use Modules\Request\Domain\Enums\StageMode;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestStageDefinition extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestStageDefinitionFactory
    {
        return RequestStageDefinitionFactory::new();
    }

    protected $fillable = [
        'request_type_version_id',
        'stage_key',
        'name',
        'position',
        'mode',
        'resolver_key',
        'resolver_config_json',
        'instructions',
        'allow_reassignment',
        'sla_minutes',
        'warning_minutes_before',
        'grace_minutes',
        'timeout_action',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'mode' => StageMode::class,
            'resolver_config_json' => 'array',
            'allow_reassignment' => 'boolean',
            'sla_minutes' => 'integer',
            'warning_minutes_before' => 'integer',
            'grace_minutes' => 'integer',
        ];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'request_type_version_id');
    }
}
