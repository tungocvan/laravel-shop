<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntSourceProvenanceBridgeContractTest extends TestCase
{
    public function test_existing_active_muasamcong_source_reference_is_resolved_without_rewriting_source_id(): void
    {
        $bridge = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        $this->assertNotFalse($bridge);
        foreach ([
            "whereHas('sources'",
            "where('source_system', 'muasamcong')",
            "where('source_record_type', 'kqlcnt_award_item')",
            "where('source_reference', 'muasamcong_kqlcnt_award_items:'.\$line->id)",
            "where('is_active', true)",
            "verifiedProvenanceProductIds(\$awardItemId)",
            "whereDoesntHave('sources')",
            "DrugBidAward::SOURCE_MANUAL",
            "DrugBidAward::SOURCE_MUASAMCONG",
            "if (\$ids !== \$legacyIds)",
        ] as $contract) {
            $this->assertStringContainsString($contract, $bridge);
        }
        foreach (['->save(', '->update(', '->delete(', '->insert('] as $mutation) {
            $this->assertStringNotContainsString($mutation, $bridge);
        }
    }

    public function test_existing_source_provenance_cannot_be_reconfirmed_as_unlinked_import(): void
    {
        $bridge = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        $this->assertStringContainsString('if ($candidate === null || $candidate->source_id !== null || $candidate->sources()->exists())', $bridge);
        $this->assertStringContainsString('$candidate->sources()->count() === 1', $bridge);
        $this->assertStringContainsString("->where('source_reference', '!=', 'muasamcong_kqlcnt_award_items:'.\$line->id)", $bridge);
    }
}
