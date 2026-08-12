<?php

namespace Modules\Website\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WebsiteSection extends Model
{
    protected $fillable = [
        'website_page_id',
        'key',
        'type',
        'position',
        'is_enabled',
        'variant',
        'config',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
            'is_enabled' => 'boolean',
            'config' => 'array',
        ];
    }

    public function page(): BelongsTo
    {
        return $this->belongsTo(WebsitePage::class, 'website_page_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(WebsiteSectionItem::class)->orderBy('position')->orderBy('id');
    }

    public function scopeEnabled(Builder $query): Builder
    {
        return $query->where('is_enabled', true);
    }
}
