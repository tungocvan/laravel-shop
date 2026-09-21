<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Modules\Pharma\Models\SupplierTracking;

class Partner extends Model
{
    protected $table = 'partners';

    protected $fillable = [
        'tax_code',
        'name',
        'legal_type',
        'partner_types',
        'phone',
        'email',
        'contact_person',
        'address',
        'province_code',
        'source',
        'status',
        'note',
    ];

    protected $casts = [
        'partner_types' => 'array',
    ];

    public const LEGAL_TYPES = [
        'company' => 'Công ty',
        'business_household' => 'Hộ kinh doanh',
        'hospital' => 'Bệnh viện',
        'individual' => 'Cá nhân',
        'other' => 'Khác',
    ];

    public const PARTNER_TYPES = [
        'supplier' => 'Nhà cung cấp',
        'customer' => 'Khách hàng',
    ];

    public const SOURCES = [
        'manual' => 'Nhập tay',
        'import' => 'Import',
        'system' => 'Hệ thống',
    ];

    public const STATUSES = [
        'active' => 'Đang hoạt động',
        'inactive' => 'Ngưng hoạt động',
        'pending' => 'Chờ xử lý',
    ];

    public function scopeWithPartnerType(Builder $query, string $type): Builder
    {
        if (! array_key_exists($type, self::PARTNER_TYPES)) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function (Builder $roles) use ($type): void {
            // Canonical storage is a JSON array. The LIKE fallbacks keep legacy
            // records searchable until they are re-saved through PartnerService.
            $roles->whereJsonContains('partner_types', $type)
                ->orWhere('partner_types', $type)
                ->orWhere('partner_types', 'like', '%"'.$type.'"%')
                ->orWhere('partner_types', 'like', '%'.$type.'%');
        });
    }

    public function hasPartnerType(string $type): bool
    {
        return collect($this->partner_types ?? [])
            ->map(fn ($value) => strtolower(trim((string) $value)))
            ->contains(strtolower(trim($type)));
    }

    public function sourceReferences(): HasMany
    {
        return $this->hasMany(PartnerSourceReference::class);
    }

    public function getLegalTypeLabelAttribute(): string
    {
        return self::LEGAL_TYPES[$this->legal_type] ?? $this->legal_type;
    }

    public function getSourceLabelAttribute(): string
    {
        return self::SOURCES[$this->source] ?? $this->source;
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function getPartnerTypeLabelsAttribute(): string
    {
        return collect($this->partner_types ?? [])
            ->map(fn ($type) => self::PARTNER_TYPES[$type] ?? $type)
            ->implode(', ');
    }
    public function supplierTrackings(): HasMany
    {
        return $this->hasMany(SupplierTracking::class, 'partner_id');
    }
}
