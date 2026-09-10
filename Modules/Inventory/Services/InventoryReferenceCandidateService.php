<?php

namespace Modules\Inventory\Services;

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Modules\Inventory\Models\InvoiceInboxLine;
use Modules\Pharma\Models\Medicine;
use Modules\Product\Models\Product;

final class InventoryReferenceCandidateService
{
    /**
     * Product and Pharma are reference sources only. This service never assigns
     * inventory_item_id; InventoryItem remains the canonical stock master.
     */
    public function candidatesFor(InvoiceInboxLine $line): array
    {
        if ($line->classification === 'NON_STOCK') {
            return [];
        }

        return array_values(array_merge(
            $this->productCandidates($line),
            $this->pharmaCandidates($line),
        ));
    }

    private function productCandidates(InvoiceInboxLine $line): array
    {
        if (! Schema::hasTable('wp_products')) {
            return [];
        }

        $descriptionKey = $this->key((string) $line->description_snapshot);
        if ($descriptionKey === '') {
            return [];
        }

        return Product::query()
            ->where('is_active', true)
            ->get(['id', 'title', 'is_active'])
            ->filter(fn (Product $product): bool => $this->key((string) $product->title) === $descriptionKey)
            ->take(3)
            ->map(fn (Product $product): array => [
                'source' => 'Product',
                'reference_id' => (int) $product->id,
                'label' => (string) $product->title,
                'confidence' => 'HIGH',
                'reason' => 'exact_product_title',
                'evidence' => [
                    'description_key' => $descriptionKey,
                    'product_title_key' => $this->key((string) $product->title),
                ],
                'auto_match_eligible' => false,
                'blocked_reasons' => ['reference_only_no_inventory_item_link'],
            ])
            ->values()
            ->all();
    }

    private function pharmaCandidates(InvoiceInboxLine $line): array
    {
        if (! Schema::hasTable('pharma_medicines')) {
            return [];
        }

        $anchor = $this->pharmaIdentityAnchor((string) $line->description_snapshot);
        if ($anchor === '') {
            return [];
        }

        $metadata = is_array($line->metadata) ? $line->metadata : [];
        $invoiceStrength = $this->key((string) ($metadata['strength'] ?? ''));
        $invoiceDosageForm = $this->key((string) ($metadata['dosage_form'] ?? ''));
        $invoicePackage = $this->key((string) ($metadata['package_spec'] ?? ''));
        $invoiceUom = $this->key((string) $line->source_uom);

        $medicines = Medicine::query()
            ->where(function ($query) use ($anchor): void {
                $query->whereRaw('LOWER(name) = ?', [$anchor])
                    ->orWhereRaw('LOWER(active_ingredients) = ?', [$anchor])
                    ->orWhereRaw('LOWER(name) LIKE ?', [$anchor.'%'])
                    ->orWhereRaw('LOWER(active_ingredients) LIKE ?', [$anchor.'%']);
            })
            ->limit(10)
            ->get();

        return $medicines
            ->map(function (Medicine $medicine) use ($anchor, $invoiceStrength, $invoiceDosageForm, $invoicePackage, $invoiceUom): array {
                $nameKey = $this->key((string) $medicine->name);
                $ingredientKey = $this->key((string) $medicine->active_ingredients);
                $concentrationKey = $this->key((string) $medicine->concentration);
                $dosageFormKey = $this->key((string) $medicine->dosage_form);
                $packageKey = $this->key((string) $medicine->packaging_specification);
                $unitKey = $this->key((string) $medicine->unit);

                $score = 0;
                $evidence = [];

                if ($ingredientKey === $anchor) {
                    $score += 40;
                    $evidence['active_ingredient'] = 'exact';
                } elseif ($nameKey === $anchor || str_starts_with($nameKey, $anchor.' ')) {
                    $score += 35;
                    $evidence['name'] = 'exact_or_prefix';
                }

                if ($invoiceStrength !== '') {
                    if ($concentrationKey === $invoiceStrength) {
                        $score += 25;
                        $evidence['strength'] = 'exact';
                    } elseif ($concentrationKey !== '') {
                        $score -= 25;
                        $evidence['strength'] = 'mismatch';
                    }
                }

                if ($invoiceUom !== '' && $unitKey !== '') {
                    if ($invoiceUom === $unitKey) {
                        $score += 10;
                        $evidence['unit'] = 'exact';
                    } else {
                        $score -= 10;
                        $evidence['unit'] = 'mismatch';
                    }
                }

                if ($invoiceDosageForm !== '' && $dosageFormKey !== '') {
                    if ($invoiceDosageForm === $dosageFormKey) {
                        $score += 15;
                        $evidence['dosage_form'] = 'exact';
                    } else {
                        $score -= 15;
                        $evidence['dosage_form'] = 'mismatch';
                    }
                }

                if ($invoicePackage !== '' && $packageKey !== '') {
                    if ($invoicePackage === $packageKey) {
                        $score += 10;
                        $evidence['package'] = 'exact';
                    } else {
                        $evidence['package'] = 'different_or_unconfirmed';
                    }
                }

                $blocked = $this->pharmaBlockedReasons($medicine);

                return [
                    'source' => 'Pharma',
                    'reference_id' => (int) $medicine->id,
                    'label' => trim(implode(' · ', array_filter([
                        $medicine->name,
                        $medicine->concentration,
                        $medicine->dosage_form,
                        $medicine->unit,
                    ]))),
                    'confidence' => $score >= 70 && $blocked === [] ? 'HIGH' : ($score >= 40 ? 'MEDIUM' : 'LOW'),
                    'score' => $score,
                    'reason' => 'deterministic_pharma_identity_candidate',
                    'evidence' => $evidence + [
                        'identity_status' => $medicine->identity_status,
                        'registration_number' => $medicine->registration_number,
                    ],
                    // Candidate evidence alone must never mutate InventoryItem ownership.
                    'auto_match_eligible' => false,
                    'blocked_reasons' => array_values(array_unique(array_merge(
                        $blocked,
                        ['reference_only_no_inventory_item_link'],
                    ))),
                ];
            })
            ->sortByDesc('score')
            ->take(5)
            ->values()
            ->all();
    }

    private function pharmaBlockedReasons(Medicine $medicine): array
    {
        $blocked = [];
        $identityStatus = (string) $medicine->identity_status;

        if (in_array($identityStatus, [
            Medicine::IDENTITY_UNVERIFIED,
            Medicine::IDENTITY_PROVISIONAL,
            Medicine::IDENTITY_AMBIGUOUS,
        ], true)) {
            $blocked[] = 'identity_status_'.$identityStatus;
        }

        $registration = Str::upper(trim((string) $medicine->registration_number));
        if ($registration !== '' && str_starts_with($registration, 'DEMO-')) {
            $blocked[] = 'demo_registration';
        }

        if (Str::contains(Str::lower((string) $medicine->notes), 'demo')) {
            $blocked[] = 'demo_record';
        }

        return $blocked;
    }

    private function pharmaIdentityAnchor(string $description): string
    {
        $value = Str::of($description)
            ->lower()
            ->ascii()
            ->replaceMatches('/\b\d+(?:[.,]\d+)?\s*(?:mg|g|mcg|ml|iu|ui|%)\b.*$/', '')
            ->replaceMatches('/[\(\[;,].*$/', '')
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();

        return trim($value);
    }

    private function key(string $value): string
    {
        return Str::of($value)
            ->lower()
            ->ascii()
            ->replaceMatches('/[^a-z0-9]+/', ' ')
            ->squish()
            ->toString();
    }
}
