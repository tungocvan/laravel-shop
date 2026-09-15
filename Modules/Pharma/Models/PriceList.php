<?php

namespace Modules\Pharma\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Partner\Models\Partner;

class PriceList extends Model
{
    public const TYPE_GLOBAL = 'global';

    public const TYPE_CUSTOMER = 'customer';

    public const STATUS_DRAFT = 'draft';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'pharma_price_lists';

    protected $fillable = [
        'code',
        'name',
        'type',
        'partner_id',
        'manager_user_id',
        'purpose_id',
        'source_price_list_id',
        'status',
        'effective_from',
        'effective_to',
        'currency',
        'priority',
        'notes',
        'created_by',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'effective_from' => 'date',
        'effective_to' => 'date',
        'priority' => 'integer',
        'approved_at' => 'datetime',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(PriceListItem::class, 'price_list_id');
    }

    public function partner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'partner_id');
    }

    public function manager(): BelongsTo
    {
        return $this->belongsTo(User::class, 'manager_user_id');
    }

    public function purpose(): BelongsTo
    {
        return $this->belongsTo(PriceListPurpose::class, 'purpose_id');
    }

    public function sourcePriceList(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_price_list_id');
    }

    public function scopeActiveAt(Builder $query, mixed $date): Builder
    {
        return $query
            ->where('status', self::STATUS_ACTIVE)
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_from')->orWhereDate('effective_from', '<=', $date);
            })
            ->where(function (Builder $query) use ($date): void {
                $query->whereNull('effective_to')->orWhereDate('effective_to', '>=', $date);
            });
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }
}
