<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PartnerSyncCandidate extends Model
{
    protected $table = 'partner_sync_candidates';

    protected $fillable = [
        'source',
        'tax_code',
        'name',
        'address',
        'email',
        'phone',
        'partner_types',
        'status',
        'matched_partner_id',
        'conflict_fields',
        'source_metadata',
        'first_seen_at',
        'last_seen_at',
    ];

    protected $casts = [
        'partner_types' => 'array',
        'conflict_fields' => 'array',
        'source_metadata' => 'array',
        'first_seen_at' => 'datetime',
        'last_seen_at' => 'datetime',
    ];

    public const STATUSES = [
        'pending' => 'Chờ xử lý',
        'matched' => 'Đã khớp',
        'conflict' => 'Cần xem xét',
        'ignored' => 'Bỏ qua',
    ];

    public function matchedPartner(): BelongsTo
    {
        return $this->belongsTo(Partner::class, 'matched_partner_id');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}
