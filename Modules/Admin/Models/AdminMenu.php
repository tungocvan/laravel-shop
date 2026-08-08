<?php

namespace Modules\Admin\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class AdminMenu extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'name', 'slug', 'url', 'icon', 'can', 'parent_id', 'is_active', 'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'sort_order' => 'integer',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order');
    }

    public function scopeMenu(Builder $query): Builder
    {
        return $query;
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public static function clearMenuCache(): void
    {
        Cache::forget(config('menu.cache.key', 'admin.menus'));
    }

    protected static function booted(): void
    {
        static::saved(fn () => self::clearMenuCache());
        static::deleted(fn () => self::clearMenuCache());
        static::restored(fn () => self::clearMenuCache());
    }
}
