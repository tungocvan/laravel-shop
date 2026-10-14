<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderFinalSecurityGateTest extends TestCase
{
    public function test_browse_and_submit_require_exact_active_medicine_variant_and_package(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));

        $this->assertSame(2, substr_count($controller, "->from('pharma_price_list_items as exact_pli')"));
        $this->assertSame(2, substr_count($controller, "->whereColumn('exact_pli.medicine_variant_id', 'pharma_product_links.medicine_variant_id')"));
        $this->assertSame(2, substr_count($controller, "->whereColumn('exact_pli.medicine_package_id', 'pharma_product_links.medicine_package_id')"));
        $this->assertSame(2, substr_count($controller, "->where('exact_pli.status', 'active')"));
        $this->assertSame(2, substr_count($controller, "->whereNull('exact_pli.medicine_package_id')"));
        $this->assertStringContainsString("->where('status', 'active')", $controller);
    }

    public function test_repeated_handoff_fails_closed_and_never_creates_another_issue(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderIssueHandoffService.php'));
        $this->assertSame(2, substr_count($service, 'if ($request->inventory_issue_id)'));
        $this->assertSame(2, substr_count($service, "throw ValidationException::withMessages([\n                    'handoff' => 'Yêu cầu đã liên kết phiếu xuất."));
        $this->assertStringNotContainsString('return InventoryIssue::query()->findOrFail($request->inventory_issue_id)', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
    }
}
