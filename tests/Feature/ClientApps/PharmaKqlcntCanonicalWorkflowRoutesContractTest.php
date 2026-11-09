<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntCanonicalWorkflowRoutesContractTest extends TestCase
{
    public function test_canonical_routes_reuse_shared_bid_award_handlers_without_redirecting_to_legacy_for_get(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));

        foreach ([
            'kqlcnt-awards.allocation.setup',
            'kqlcnt-awards.allocation.hospital',
            'kqlcnt-awards.allocation.hospital.store',
            'kqlcnt-awards.allocation.hospital.policy',
            'kqlcnt-awards.allocation.hospital.policy.store',
            'kqlcnt-awards.commercial-policy.store',
        ] as $name) {
            $this->assertStringContainsString("name('".$name."')", $routes);
        }

        $this->assertTrue(class_exists(\Modules\Pharma\Services\ClientBidAwardWorkflow::class));
        $this->assertStringContainsString('use Modules\\Pharma\\Services\\ClientBidAwardWorkflow;', $controller);
        $this->assertStringNotContainsString('use Modules\\ClientPortal\\Applications\\Pharma\\Services\\ClientBidAwardWorkflow;', $controller);
        $this->assertStringContainsString('private function linkedScope(', $controller);
        $this->assertStringContainsString('abort_if($scope === null, 409', $controller);
        $this->assertStringContainsString('->bidAwardAllocation(', $controller);
        $this->assertStringContainsString('->bidAwardCommercialPolicy(', $controller);
        $this->assertStringContainsString('->storeBidAwardDistributionSetup(', $controller);
        $this->assertStringContainsString('->storeBidAwardHospitalAllocation(', $controller);
        $this->assertStringContainsString('->storeBidAwardHospitalCommercialPolicy(', $controller);
    }

    public function test_shared_views_use_canonical_routes_when_kqlcnt_context_is_supplied(): void
    {
        foreach ([
            'bid-award-allocation.blade.php',
            'bid-award-hospital-allocation.blade.php',
            'bid-award-hospital-policy.blade.php',
            'bid-award-commercial-policy.blade.php',
        ] as $view) {
            $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/'.$view));
            $this->assertStringContainsString('isset($kqlcntAwardItem)', $blade, $view);
            $this->assertStringContainsString('client.pharma.kqlcnt-awards.', $blade, $view);
            $this->assertStringContainsString('client.pharma.bid-awards.', $blade, $view);
        }
    }
}
