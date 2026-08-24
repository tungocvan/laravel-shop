<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Request\Database\Factories\RequestTypeAudienceFactory;
use Modules\Request\Domain\Enums\AudienceActorType;
use Modules\Request\Domain\Enums\AudienceCapability;

class RequestTypeAudience extends Model
{
    use HasFactory;

    protected static function newFactory(): RequestTypeAudienceFactory
    {
        return RequestTypeAudienceFactory::new();
    }

    protected $fillable = ['request_type_version_id', 'actor_type', 'actor_id', 'capability'];

    protected function casts(): array
    {
        return ['actor_type' => AudienceActorType::class, 'actor_id' => 'integer', 'capability' => AudienceCapability::class];
    }

    public function version(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'request_type_version_id');
    }
}
