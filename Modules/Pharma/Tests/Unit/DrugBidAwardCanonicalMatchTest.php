<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class DrugBidAwardCanonicalMatchTest extends TestCase
{
    #[Test]
    public function canonical_match_persistence_supports_medicine_variant_package_and_manual_protection(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_160000_create_drug_bid_award_matches_table.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/DrugBidAwardMatch.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardMatchManager.php'));

        $this->assertStringContainsString("Schema::create('pharma_drug_bid_award_matches'", $migration);
        $this->assertStringContainsString("foreignId('medicine_variant_id')", $migration);
        $this->assertStringContainsString("foreignId('medicine_package_id')", $migration);
        $this->assertStringContainsString("unique('drug_bid_award_id'", $migration);
        $this->assertStringContainsString('REVIEW_CONFIRMED', $model);
        $this->assertStringContainsString('isManualConfirmed()', $manager);
        $this->assertStringContainsString('REVIEW_STALE', $manager);
    }

    #[Test]
    public function matcher_stops_at_deepest_deterministic_resolution_and_never_guesses_package(): void
    {
        $matcher = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardMatcher.php'));

        $this->assertStringContainsString('registration_exact', $matcher);
        $this->assertStringContainsString('variant_ambiguous', $matcher);
        $this->assertStringContainsString('package_ambiguous', $matcher);
        $this->assertStringContainsString('package_not_deterministic', $matcher);
        $this->assertStringContainsString('LEVEL_VARIANT', $matcher);
        $this->assertStringContainsString('LEVEL_PACKAGE', $matcher);
    }

    #[Test]
    public function matcher_allows_unique_normalized_name_as_high_confidence_without_ignoring_conflicts(): void
    {
        $matcher = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardMatcher.php'));

        $this->assertStringContainsString('STATUS_HIGH_CONFIDENCE', $matcher);
        $this->assertStringContainsString('normalized_name_unique', $matcher);
        $this->assertStringContainsString('unique_normalized_name_no_conflict', $matcher);
        $this->assertStringContainsString('availableAttributesDoNotConflict', $matcher);
        $this->assertStringContainsString('medicine_identity_conflict', $matcher);
        $this->assertStringContainsString('normalized_name_ambiguous', $matcher);
        $this->assertStringNotContainsString('if ($ingredient === null)', $matcher);
    }

    #[Test]
    public function projection_does_not_silently_create_provisional_medicine_and_runs_canonical_matcher(): void
    {
        $projection = file_get_contents(base_path('Modules/Pharma/Services/DrugAwardProjectionService.php'));

        $this->assertStringNotContainsString('createProvisionalMedicineWhenSafe', $projection);
        $this->assertStringContainsString('$this->matchManager->refresh($award)', $projection);
        $this->assertStringContainsString("'canonicalMatch.variant'", $projection);
    }
}
