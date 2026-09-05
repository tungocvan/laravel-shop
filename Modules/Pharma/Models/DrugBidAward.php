<?php

namespace Modules\Pharma\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DrugBidAward extends Model
{
    public const SOURCE_MANUAL = 'manual';

    public const SOURCE_MUASAMCONG = 'muasamcong';

    public const MATCH_VERIFIED = 'verified';

    public const MATCH_PROVISIONAL = 'provisional';

    public const MATCH_AMBIGUOUS = 'ambiguous';

    public const MATCH_UNRESOLVED = 'unresolved';

    protected $table = 'pharma_drug_bid_awards';

    protected $fillable = [
        'canonical_identity_key',
        'medicine_id',
        'medicine_code',
        'medicine_match_status',
        'medicine_name',
        'active_ingredient',
        'concentration',
        'route',
        'dosage_form',
        'unit',
        'drug_group',
        'packaging_specification',
        'shelf_life_months',
        'registration_or_import_license',
        'manufacturer',
        'country',
        'quantity',
        'price_plan',
        'winning_price',
        'amount',
        'unit_price',
        'bidding_notice_code',
        'lot_no',
        'lot_name',
        'investor_code',
        'investor_name',
        'decision_number',
        'decision_date',
        'published_at',
        'contract_no',
        'contract_duration_months',
        'contract_period',
        'contract_period_unit',
        'contract_period_text',
        'effect_frame_period',
        'winning_company_name',
        'contractor_code',
        'decision_document_url',
        'is_active',
        'source_type',
        'source_id',
        'source_synced_at',
        'source_payload_hash',
    ];

    protected $casts = [
        'medicine_id' => 'integer',
        'quantity' => 'decimal:4',
        'price_plan' => 'decimal:4',
        'winning_price' => 'decimal:4',
        'amount' => 'decimal:4',
        'unit_price' => 'decimal:2',
        'decision_date' => 'date',
        'published_at' => 'datetime',
        'contract_duration_months' => 'integer',
        'contract_period' => 'integer',
        'shelf_life_months' => 'integer',
        'is_active' => 'boolean',
        'source_synced_at' => 'datetime',
    ];

    public function medicine(): BelongsTo
    {
        return $this->belongsTo(Medicine::class, 'medicine_id');
    }

    public function sources(): HasMany
    {
        return $this->hasMany(DrugBidAwardSource::class, 'drug_bid_award_id');
    }

    public function isExternalSource(): bool
    {
        return $this->source_type !== self::SOURCE_MANUAL || $this->sources()->exists();
    }

    /**
     * Return the effective drug-profile value while preserving provenance.
     * Award snapshots always win. HSSP only fills missing attributes.
     *
     * @return array{value:mixed,origin:string}
     */
    public function effectiveMedicineAttribute(string $attribute): array
    {
        $hsspMap = [
            'medicine_name' => 'name',
            'active_ingredient' => 'active_ingredients',
            'concentration' => 'concentration',
            'route' => 'route_of_administration',
            'dosage_form' => 'dosage_form',
            'unit' => 'unit',
            'packaging_specification' => 'packaging_specification',
            'shelf_life_months' => 'shelf_life_months',
            'manufacturer' => 'manufacturing_company',
            'country' => 'manufacturing_country',
        ];

        $awardValue = $this->getAttribute($attribute);
        if ($awardValue !== null && $awardValue !== '') {
            return ['value' => $awardValue, 'origin' => 'award'];
        }

        $medicineAttribute = $hsspMap[$attribute] ?? null;
        $medicineValue = $medicineAttribute ? $this->medicine?->getAttribute($medicineAttribute) : null;

        if ($medicineValue !== null && $medicineValue !== '') {
            return ['value' => $medicineValue, 'origin' => 'hssp'];
        }

        return ['value' => null, 'origin' => 'missing'];
    }
}
