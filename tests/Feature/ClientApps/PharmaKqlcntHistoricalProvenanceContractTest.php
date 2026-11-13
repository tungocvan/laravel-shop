<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

final class PharmaKqlcntHistoricalProvenanceContractTest extends TestCase
{
    #[Test]
    public function corrected_contractor_display_name_does_not_discard_exact_provenance(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));

        $this->assertStringNotContainsString("->where('winning_company_name', \$contractorName)", $source);
        $this->assertStringContainsString("->where('contractor_code', \$line->contractor_code)", $source);
        $this->assertStringContainsString("trim((string) \$line->contractor_code) === ''", $source);
        $this->assertStringContainsString("->where('source_reference', 'muasamcong_kqlcnt_award_items:'.\$line->id)", $source);
        $this->assertStringContainsString("->where('is_active', true)", $source);
        $this->assertStringContainsString('sameDecimal($candidate->quantity, $line->quantity)', $source);
        $this->assertStringContainsString('sameDecimal($candidate->winning_price, $line->winning_price)', $source);
        $this->assertStringContainsString('if ($matches->count() !== 1)', $source);
    }

    #[Test]
    public function readiness_uses_verified_full_group_provenance_fallback(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntWorkflowReadiness.php'));

        $this->assertStringContainsString('app(KqlcntLegacyWorkflowBridge::class)->scopeKey($awardItemId)', $source);
        $this->assertStringContainsString("'linked_count' => \$count", $source);
        $this->assertStringContainsString("'status' => \$identityMatches ? 'linked' : 'requires_mapping'", $source);
    }
}
