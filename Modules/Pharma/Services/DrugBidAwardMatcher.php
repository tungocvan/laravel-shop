<?php

namespace Modules\Pharma\Services;

use Modules\Pharma\Data\DrugBidMatchResult;
use Modules\Pharma\Models\DrugBidAward;
use Modules\Pharma\Models\DrugBidAwardMatch;
use Modules\Pharma\Models\Medicine;
use Modules\Pharma\Models\MedicinePackage;
use Modules\Pharma\Models\MedicineVariant;

class DrugBidAwardMatcher
{
    public function __construct(private ?MedicineCatalogNormalizer $normalizer = null)
    {
        $this->normalizer ??= new MedicineCatalogNormalizer;
    }

    public function match(DrugBidAward $award): DrugBidMatchResult
    {
        $medicineResult = $this->resolveMedicine($award);
        if (! $medicineResult->medicine) {
            return $medicineResult;
        }

        $variants = MedicineVariant::query()
            ->where('medicine_id', $medicineResult->medicine->id)
            ->where('status', 'active')
            ->get();

        $strength = $this->normalizer->text($award->concentration);
        $variantMatches = $variants->filter(function (MedicineVariant $variant) use ($strength): bool {
            if ($strength === null) {
                return false;
            }

            return $this->normalizer->text($variant->strength_normalized ?: $variant->strength_text) === $strength;
        })->values();

        if ($variantMatches->count() !== 1) {
            return new DrugBidMatchResult(
                $medicineResult->medicine,
                null,
                null,
                $variantMatches->count() > 1 ? DrugBidAwardMatch::STATUS_REVIEW_REQUIRED : $medicineResult->status,
                $medicineResult->method,
                $medicineResult->confidence,
                DrugBidAwardMatch::LEVEL_MEDICINE,
                $variantMatches->count() > 1 ? 'variant_ambiguous' : 'variant_not_deterministic',
            );
        }

        $variant = $variantMatches->first();
        $packaging = $this->normalizer->text($award->packaging_specification);
        if ($packaging === null) {
            return new DrugBidMatchResult($medicineResult->medicine, $variant, null, $medicineResult->status, $medicineResult->method.'_variant', $medicineResult->confidence, DrugBidAwardMatch::LEVEL_VARIANT);
        }

        $packages = MedicinePackage::query()->where('medicine_variant_id', $variant->id)->get()
            ->filter(fn (MedicinePackage $package) => $this->normalizer->text($package->packaging_normalized ?: $package->packaging_text) === $packaging)
            ->values();

        if ($packages->count() === 1) {
            return new DrugBidMatchResult($medicineResult->medicine, $variant, $packages->first(), $medicineResult->status, $medicineResult->method.'_variant_package', $medicineResult->confidence, DrugBidAwardMatch::LEVEL_PACKAGE);
        }

        return new DrugBidMatchResult(
            $medicineResult->medicine,
            $variant,
            null,
            $packages->count() > 1 ? DrugBidAwardMatch::STATUS_REVIEW_REQUIRED : $medicineResult->status,
            $medicineResult->method.'_variant',
            $medicineResult->confidence,
            DrugBidAwardMatch::LEVEL_VARIANT,
            $packages->count() > 1 ? 'package_ambiguous' : 'package_not_deterministic',
        );
    }

    public function sourceIdentityHash(DrugBidAward $award): string
    {
        return hash('sha256', implode('|', [
            $this->normalizer->registration($award->registration_or_import_license) ?? '-',
            $this->normalizer->identityPart($award->medicine_name),
            $this->normalizer->identityPart($award->active_ingredient),
            $this->normalizer->identityPart($award->concentration),
            $this->normalizer->identityPart($award->dosage_form),
            $this->normalizer->identityPart($award->packaging_specification),
            $this->normalizer->identityPart($award->manufacturer),
        ]));
    }

    private function resolveMedicine(DrugBidAward $award): DrugBidMatchResult
    {
        $registration = $this->normalizer->registration($award->registration_or_import_license);
        if ($registration !== null) {
            $matches = Medicine::query()->where(function ($query) use ($registration): void {
                $query->where('registration_number_primary', 'like', '%'.$registration.'%')
                    ->orWhere('registration_number', 'like', '%'.$registration.'%');
            })->get()->filter(fn (Medicine $medicine) => in_array($registration, array_filter([
                $this->normalizer->registration($medicine->registration_number_primary),
                $this->normalizer->registration($medicine->registration_number),
            ]), true))->values();

            if ($matches->count() === 1) {
                return new DrugBidMatchResult($matches->first(), null, null, DrugBidAwardMatch::STATUS_EXACT, 'registration_exact', 100, DrugBidAwardMatch::LEVEL_MEDICINE);
            }

            if ($matches->count() > 1) {
                return new DrugBidMatchResult(null, null, null, DrugBidAwardMatch::STATUS_REVIEW_REQUIRED, 'registration_ambiguous', 0, null, 'medicine_ambiguous');
            }
        }

        $name = $this->normalizer->text($award->medicine_name);
        if ($name === null) {
            return new DrugBidMatchResult(null, null, null, DrugBidAwardMatch::STATUS_UNMATCHED, 'insufficient_identity', 0, null, 'medicine_unmatched');
        }

        $nameMatches = Medicine::query()->where('name', 'like', '%'.trim((string) $award->medicine_name).'%')->get()
            ->filter(fn (Medicine $medicine) => $this->normalizer->text($medicine->name) === $name)
            ->values();

        $ingredient = $this->normalizer->text($award->active_ingredient);
        $manufacturer = $this->normalizer->text($award->manufacturer);

        if ($ingredient !== null) {
            $identityMatches = $nameMatches->filter(fn (Medicine $medicine) => $this->normalizer->text($medicine->active_ingredients) === $ingredient
                && ($manufacturer === null || $this->normalizer->text($medicine->manufacturing_company) === $manufacturer))
                ->values();

            if ($identityMatches->count() === 1) {
                return new DrugBidMatchResult($identityMatches->first(), null, null, DrugBidAwardMatch::STATUS_EXACT, 'normalized_medicine_exact', 95, DrugBidAwardMatch::LEVEL_MEDICINE);
            }

            if ($identityMatches->count() > 1) {
                return new DrugBidMatchResult(null, null, null, DrugBidAwardMatch::STATUS_REVIEW_REQUIRED, 'normalized_medicine_ambiguous', 0, null, 'medicine_ambiguous');
            }

            // Source attributes are present but contradict Medicine Master: never fall back to name-only.
            if ($nameMatches->isNotEmpty()) {
                return new DrugBidMatchResult(null, null, null, DrugBidAwardMatch::STATUS_REVIEW_REQUIRED, 'medicine_identity_conflict', 0, null, 'medicine_identity_conflict');
            }
        }

        if ($nameMatches->count() === 1 && $this->availableAttributesDoNotConflict($award, $nameMatches->first())) {
            return new DrugBidMatchResult(
                $nameMatches->first(),
                null,
                null,
                DrugBidAwardMatch::STATUS_HIGH_CONFIDENCE,
                'normalized_name_unique',
                90,
                DrugBidAwardMatch::LEVEL_MEDICINE,
                'unique_normalized_name_no_conflict',
            );
        }

        return new DrugBidMatchResult(
            null,
            null,
            null,
            $nameMatches->count() > 1 ? DrugBidAwardMatch::STATUS_REVIEW_REQUIRED : DrugBidAwardMatch::STATUS_UNMATCHED,
            $nameMatches->count() > 1 ? 'normalized_name_ambiguous' : 'normalized_medicine_unmatched',
            0,
            null,
            $nameMatches->count() > 1 ? 'medicine_ambiguous' : 'medicine_unmatched',
        );
    }

    private function availableAttributesDoNotConflict(DrugBidAward $award, Medicine $medicine): bool
    {
        $checks = [
            [$award->active_ingredient, $medicine->active_ingredients],
            [$award->manufacturer, $medicine->manufacturing_company],
            [$award->dosage_form, $medicine->dosage_form],
        ];

        foreach ($checks as [$source, $canonical]) {
            $sourceNormalized = $this->normalizer->text($source);
            $canonicalNormalized = $this->normalizer->text($canonical);
            if ($sourceNormalized !== null && $canonicalNormalized !== null && $sourceNormalized !== $canonicalNormalized) {
                return false;
            }
        }

        return true;
    }
}
