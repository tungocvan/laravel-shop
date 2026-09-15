<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Medicine extends Model
{
    public const IDENTITY_UNVERIFIED = 'unverified';

    public const IDENTITY_VERIFIED_REGISTRATION = 'verified_registration';

    public const IDENTITY_EXACT_NORMALIZED = 'exact_normalized';

    public const IDENTITY_PROVISIONAL = 'provisional';

    public const IDENTITY_AMBIGUOUS = 'ambiguous';

    public const PROFILE_INCOMPLETE = 'incomplete';

    public const PROFILE_COMPLETE = 'complete';

    public const PROFILE_VERIFIED = 'verified';

    public const PROFILE_NEEDS_REVIEW = 'needs_review';

    public const CATALOG_ACTIVE = 'active';

    public const CATALOG_INACTIVE = 'inactive';

    public const CATALOG_DISCONTINUED = 'discontinued';

    public const CATALOG_SUSPENDED = 'suspended';

    public const CATALOG_NEEDS_REVIEW = 'needs_review';

    protected $table = 'pharma_medicines';

    protected $fillable = [
        'medicine_code',
        'canonical_identity_key',
        'identity_status',
        'profile_status',
        'catalog_status',
        'circular_order_number',
        'circular_group',
        'therapeutic_group',
        'active_ingredients',
        'concentration',
        'name',
        'dosage_form',
        'route_of_administration',
        'unit',
        'packaging_specification',
        'registration_number',
        'registration_number_raw',
        'registration_number_primary',
        'shelf_life',
        'shelf_life_months',
        'registered_company',
        'manufacturing_company',
        'manufacturing_country',
        'visa_validity_date',
        'gmp_certification_date',
        'declared_price',
        'is_special_control',
        'profile_link',
        'notes',
        'last_verified_at',
    ];

    protected $casts = [
        'visa_validity_date' => 'date',
        'gmp_certification_date' => 'date',
        'is_special_control' => 'boolean',
        'declared_price' => 'decimal:2',
        'shelf_life_months' => 'integer',
        'last_verified_at' => 'datetime',
    ];

    public function sources(): HasMany
    {
        return $this->hasMany(MedicineSource::class, 'medicine_id');
    }

    public function variants(): HasMany
    {
        return $this->hasMany(MedicineVariant::class, 'medicine_id');
    }

    public function aliases(): HasMany
    {
        return $this->hasMany(MedicineAlias::class, 'medicine_id');
    }

    public function profiles(): HasMany
    {
        return $this->hasMany(MedicineProfile::class, 'medicine_id');
    }

    public function currentProfile(): HasOne
    {
        return $this->hasOne(MedicineProfile::class, 'medicine_id')
            ->where('is_current', true)
            ->latestOfMany();
    }

    public function drugBidAwards(): HasMany
    {
        return $this->hasMany(DrugBidAward::class, 'medicine_id');
    }

    public function isIncomplete(): bool
    {
        return $this->profile_status === self::PROFILE_INCOMPLETE;
    }
}
