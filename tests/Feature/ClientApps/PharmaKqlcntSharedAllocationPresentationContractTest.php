<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaKqlcntSharedAllocationPresentationContractTest extends TestCase
{
    public function test_legacy_and_canonical_views_share_allocation_hero(): void
    {
        $legacy = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $canonical = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $partial = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/partials/bid-award-workflow-hero.blade.php'));

        $this->assertNotFalse($legacy);
        $this->assertNotFalse($canonical);
        $this->assertNotFalse($partial);
        $this->assertStringContainsString("partials.bid-award-workflow-hero", $legacy);
        $this->assertStringContainsString("partials.bid-award-workflow-hero", $canonical);
        $this->assertStringContainsString('rounded-[28px] bg-slate-950', $partial);
        $this->assertStringContainsString('Allocation', $legacy);
        $this->assertStringContainsString('Phạm vi & hiệu lực phân bổ', $canonical);
        $this->assertStringContainsString('Tổng quan phân bổ', $canonical);
        $this->assertStringContainsString('Bệnh viện nhận phân bổ', $canonical);
    }

    public function test_unverified_canonical_workspace_has_no_mutation_form_or_false_allocation_numbers(): void
    {
        $canonical = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/kqlcnt-workflow-preview.blade.php'));
        $this->assertStringContainsString('Chế độ kiểm tra — chưa cho phép lưu', $canonical);
        $this->assertStringContainsString('disabled', $canonical);
        $this->assertStringContainsString('Chưa xác minh', $canonical);
        $this->assertStringContainsString('mappingDiagnostics', $canonical);
        $this->assertStringNotContainsString('<form', $canonical);
        $this->assertStringNotContainsString('method="POST"', $canonical);
        $this->assertStringNotContainsString('bid-awards.allocation.setup', $canonical);
        $this->assertStringNotContainsString('bid-awards.allocation.hospital', $canonical);
    }

    public function test_existing_bid_awards_mutation_actions_remain_unchanged(): void
    {
        $legacy = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/bid-award-allocation.blade.php'));
        $this->assertStringContainsString("route('client.pharma.bid-awards.allocation.setup',\$scope)", $legacy);
        $this->assertStringContainsString("route('client.pharma.bid-awards.allocation.hospital',[\$scope,\$hospital->id])", $legacy);
        $this->assertStringContainsString('distribution-setup-form', $legacy);
    }
}
