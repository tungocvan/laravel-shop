<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntWorkflowPreviewContractTest extends TestCase
{
    public function test_canonical_workspaces_are_read_only_and_feature_gated(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/routes.php'));
        $this->assertNotFalse($routes);
        $this->assertStringContainsString("'/kqlcnt-awards/{awardItem}/allocation'", $routes);
        $this->assertStringContainsString("'/kqlcnt-awards/{awardItem}/commercial-policy'", $routes);
        $this->assertStringContainsString("->middleware('client.feature:pharma,kqlcnt-awards')", $routes);
        $this->assertStringNotContainsString("Route::post('/kqlcnt-awards/{awardItem}/allocation'", $routes);
        $this->assertStringNotContainsString("Route::post('/kqlcnt-awards/{awardItem}/commercial-policy'", $routes);
    }

    public function test_controller_checks_action_permission_and_reads_canonical_source(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaKqlcntAwardController.php'));
        $this->assertNotFalse($controller);
        $this->assertStringContainsString("client.pharma.bid-awards.allocate", $controller);
        $this->assertStringContainsString("client.pharma.bid-awards.commercial-policy", $controller);
        $this->assertStringContainsString('abort_unless(request()->user(', $controller);
        $this->assertStringContainsString("'readiness' => \$readiness->forAward(\$awardItem)", $controller);
        $this->assertStringContainsString("'medicines' => \$awards->medicines(\$awardItem)", $controller);
    }

    public function test_cards_expose_explicit_preview_links_and_workspace_has_no_forms(): void
    {
        $cards = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-awards.blade.php'));
        $preview = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $this->assertNotFalse($cards);
        $this->assertNotFalse($preview);
        $this->assertStringContainsString('@if($canAllocateAwards)', $cards);
        $this->assertStringContainsString('@if($canPolicyAwards)', $cards);
        $this->assertStringContainsString('kqlcnt-awards.allocation', $cards);
        $this->assertStringContainsString('kqlcnt-awards.commercial-policy', $cards);
        $this->assertStringContainsString('Chế độ kiểm tra', $preview);
        $this->assertStringContainsString("kqlcnt-awards.mapping.confirm", $preview);
        $this->assertStringContainsString('name="acknowledge"', $preview);
    }
}
