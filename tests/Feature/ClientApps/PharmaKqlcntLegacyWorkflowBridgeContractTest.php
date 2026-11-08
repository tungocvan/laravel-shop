<?php

namespace Tests\Feature\ClientApps;

use Modules\ClientPortal\Applications\Pharma\Services\KqlcntLegacyWorkflowBridge;
use Tests\TestCase;

class PharmaKqlcntLegacyWorkflowBridgeContractTest extends TestCase
{
    public function test_bridge_reuses_existing_pharma_group_only_for_exact_product_scope(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntLegacyWorkflowBridge.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('scopedProductIds($awardItemId)', $source);
        $this->assertStringContainsString('$this->groups->awardsQuery($award)', $source);
        $this->assertStringContainsString('sort($ids)', $source);
        $this->assertStringContainsString('sort($legacyIds)', $source);
        $this->assertStringContainsString('if ($ids !== $legacyIds)', $source);
        $this->assertStringContainsString('sha1($this->groups->resultKey($award))', $source);
        foreach (['->save(', '->update(', '->delete(', '->insert('] as $write) {
            $this->assertStringNotContainsString($write, $source);
        }
    }

    public function test_existing_allocation_and_policy_routes_are_used_without_new_mutation_routes(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertStringContainsString("'client.pharma.bid-awards.allocation'", $controller);
        $this->assertStringContainsString("'client.pharma.bid-awards.commercial-policy'", $controller);
        $this->assertStringContainsString('$bridge->scopeKey($awardItem)', $controller);
        $this->assertStringContainsString('client.pharma.bid-awards.allocate', $controller);
        $this->assertStringContainsString('client.pharma.bid-awards.commercial-policy', $controller);
        $this->assertStringNotContainsString("Route::post('/kqlcnt-awards/{awardItem}/allocation'", $routes);
        $this->assertStringNotContainsString("Route::post('/kqlcnt-awards/{awardItem}/commercial-policy'", $routes);
    }

    public function test_readiness_selects_name_needed_for_blank_contractor_code(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntWorkflowReadiness.php'));
        $this->assertStringContainsString("['id', 'source_id', 'bidding_notice_code', 'contractor_code', 'winning_company_name']", $source);
    }
}
