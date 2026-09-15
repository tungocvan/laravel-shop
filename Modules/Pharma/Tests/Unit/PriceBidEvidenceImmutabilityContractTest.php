<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\TestCase;

class PriceBidEvidenceImmutabilityContractTest extends TestCase
{
    public function test_capture_is_create_once_and_never_updates_existing_snapshot(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceBidEvidenceService.php'));

        $this->assertStringContainsString("where('price_list_item_id', \$item->id)", $service);
        $this->assertStringContainsString('if ($existing)', $service);
        $this->assertStringContainsString('return $existing;', $service);
        $this->assertStringNotContainsString('updateOrCreate(', $service);
        $this->assertStringContainsString('restoreSnapshot(', $service);
    }

    public function test_evidence_schema_is_a_snapshot_not_only_an_award_pointer(): void
    {
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceListItemBidEvidence.php'));

        foreach ([
            'source_system', 'source_record_key', 'bid_price', 'quantity', 'unit',
            'investor_name', 'contractor_name', 'decision_number', 'award_date',
            'medicine_id', 'medicine_variant_id', 'medicine_package_id',
            'captured_at', 'captured_by', 'metadata',
        ] as $field) {
            $this->assertStringContainsString("'{$field}'", $model);
        }
    }
}
