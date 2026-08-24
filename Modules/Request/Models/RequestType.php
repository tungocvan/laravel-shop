<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Request\Database\Factories\RequestTypeFactory;
use Modules\Request\Domain\Enums\RequestTypeStatus;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestType extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestTypeFactory
    {
        return RequestTypeFactory::new();
    }

    protected $fillable = ['request_group_id', 'code', 'name', 'summary', 'status', 'sort_order', 'available_from', 'available_until', 'lock_version', 'created_by', 'updated_by', 'retired_by', 'retired_at'];

    protected function casts(): array
    {
        return ['status' => RequestTypeStatus::class, 'sort_order' => 'integer', 'lock_version' => 'integer', 'available_from' => 'immutable_datetime', 'available_until' => 'immutable_datetime', 'retired_at' => 'immutable_datetime'];
    }

    public function group(): BelongsTo
    {
        return $this->belongsTo(RequestGroup::class, 'request_group_id');
    }

    public function versions(): HasMany
    {
        return $this->hasMany(RequestTypeVersion::class);
    }

    public function activeDraft(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'active_draft_version_id');
    }

    public function currentPublishedVersion(): BelongsTo
    {
        return $this->belongsTo(RequestTypeVersion::class, 'current_published_version_id');
    }
}
