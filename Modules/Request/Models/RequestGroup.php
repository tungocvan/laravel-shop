<?php

namespace Modules\Request\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Request\Database\Factories\RequestGroupFactory;
use Modules\Request\Models\Concerns\HasPublicUlid;

class RequestGroup extends Model
{
    use HasFactory, HasPublicUlid;

    protected static function newFactory(): RequestGroupFactory
    {
        return RequestGroupFactory::new();
    }

    protected $fillable = ['code', 'name', 'description', 'icon_key', 'color_key', 'sort_order', 'is_active', 'created_by', 'updated_by', 'archived_at'];

    protected function casts(): array
    {
        return ['sort_order' => 'integer', 'is_active' => 'boolean', 'archived_at' => 'immutable_datetime'];
    }

    public function types(): HasMany
    {
        return $this->hasMany(RequestType::class);
    }
}
