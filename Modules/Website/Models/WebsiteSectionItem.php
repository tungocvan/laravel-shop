<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WebsiteSectionItem extends Model
{
    protected $fillable = [
        'website_section_id',
        'reference_type',
        'reference_id',
        'position',
        'is_enabled',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'reference_id' => 'integer',
            'position' => 'integer',
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(WebsiteSection::class, 'website_section_id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
