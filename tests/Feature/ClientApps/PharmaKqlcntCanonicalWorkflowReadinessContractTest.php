<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntCanonicalWorkflowReadinessContractTest extends TestCase
{
    public function test_readiness_is_read_only_and_matches_exact_canonical_source_lines(): void
    {
        $path = base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntWorkflowReadiness.php');
        $source = file_get_contents($path);

        $this->assertNotFalse($source);
        $this->assertStringContainsString("where('source_type', 'muasamcong')", $source);
        $this->assertStringContainsString("whereIn('source_id', \$ids)", $source);
        $this->assertStringContainsString("->groupBy(fn (\$row) => (int) \$row->source_id)", $source);
        $this->assertStringContainsString("->every(fn (\$rows) => \$rows->count() === 1)", $source);
        $this->assertStringContainsString("linked->contractor_code", $source);
        $this->assertStringContainsString("'requires_mapping'", $source);
        $this->assertStringNotContainsString('->insert(', $source);
        $this->assertStringNotContainsString('->update(', $source);
        $this->assertStringNotContainsString('->delete(', $source);
    }

    public function test_legacy_bid_award_routes_remain_during_migration(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));

        $this->assertNotFalse($routes);
        $this->assertStringContainsString("'/bid-awards'", $routes);
        $this->assertStringContainsString("'/kqlcnt-awards'", $routes);
    }
}
