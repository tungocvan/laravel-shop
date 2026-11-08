<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntCanonicalWorkflowAdaptersContractTest extends TestCase
{
    public function test_allocation_adapter_uses_exact_ids_and_existing_pharma_validation(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntAllocationWorkflow.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('scopedProductIds($awardItemId)', $source);
        $this->assertStringContainsString('in_array($productId, $allowed, true)', $source);
        $this->assertStringContainsString('$this->allocations->save(', $source);
        $this->assertStringNotContainsString('awardsQuery(', $source);
    }

    public function test_commercial_adapter_checks_allocation_before_shared_policy_save(): void
    {
        $source = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Services/KqlcntCommercialPolicyWorkflow.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('scopedProductIds($awardItemId)', $source);
        $this->assertStringContainsString('DrugBidAwardAllocation::STATUS_ACTIVE', $source);
        $this->assertStringContainsString('saveProductPoliciesForIds($ids, $percentages, $actorId)', $source);
        $this->assertStringNotContainsString('awardsQuery(', $source);
    }

    public function test_legacy_policy_delegates_to_shared_explicit_scope_implementation(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Services/DrugBidAwardCommercialPolicyService.php'));
        $this->assertNotFalse($source);
        $this->assertStringContainsString('$this->saveProductPoliciesForIds($validIds, $percentages, $actorId)', $source);
        $this->assertStringContainsString('public function saveProductPoliciesForIds(', $source);
        $this->assertStringContainsString('DB::transaction(function () use ($percentages, $validIds, $actorId)', $source);
        $this->assertStringContainsString('if (! in_array($awardId, $validIds, true))', $source);
    }

    public function test_canonical_routes_are_not_prematurely_exposed(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertNotFalse($routes);
        $this->assertStringContainsString("'/bid-awards'", $routes);
        $this->assertStringContainsString("'/kqlcnt-awards'", $routes);
        $this->assertStringNotContainsString("kqlcnt-awards/{awardItem}/allocation", $routes);
    }
}
