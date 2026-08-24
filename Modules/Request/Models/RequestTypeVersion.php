<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;
use Modules\Request\Database\Factories\RequestTypeVersionFactory;
use Modules\Request\Domain\Enums\RequestTypeVersionStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestTypeVersion extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestTypeVersionFactory
    {
        return RequestTypeVersionFactory::new();
    }

    protected $fillable = ['request_type_id', 'version_number', 'status', 'title', 'description', 'requester_guidance', 'form_schema_json', 'policy_json', 'presentation_json', 'schema_version', 'canonical_checksum', 'created_from_version_id', 'published_by', 'published_at', 'retired_by', 'retired_at', 'created_by', 'updated_by'];

    protected static function booted(): void
    {
        static::updating(function (self $version): void {
            if ($version->getRawOriginal('status') !== RequestTypeVersionStatus::Draft->value) {
                throw new LogicException('Published Request type versions are immutable.');
            }
        });
        static::deleting(function (self $version): void {
            if ($version->status !== RequestTypeVersionStatus::Draft) {
                throw new LogicException('Published Request type versions cannot be deleted.');
            }
        });
    }

    protected function casts(): array
    {
        return ['version_number' => 'integer', 'status' => RequestTypeVersionStatus::class, 'form_schema_json' => 'array', 'policy_json' => 'array', 'presentation_json' => 'array', 'schema_version' => 'integer', 'published_at' => 'immutable_datetime', 'retired_at' => 'immutable_datetime'];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(RequestType::class, 'request_type_id');
    }

    public function audiences(): HasMany
    {
        return $this->hasMany(RequestTypeAudience::class);
    }

    public function stages(): HasMany
    {
        return $this->hasMany(RequestStageDefinition::class)->orderBy('position');
    }
}
