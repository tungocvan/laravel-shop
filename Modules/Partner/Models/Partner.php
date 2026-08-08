<?php

namespace Modules\Partner\Models;

use Illuminate\Database\Eloquent\Model;

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
}
