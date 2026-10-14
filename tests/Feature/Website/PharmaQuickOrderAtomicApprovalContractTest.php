<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderAtomicApprovalContractTest extends TestCase
{
    public function test_acceptance_and_draft_are_atomic_and_price_is_derived_from_configured_sources(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderIssueHandoffService.php'));
        $this->assertStringContainsString('function acceptAndCreateDraft(', $service);
        $this->assertStringContainsString('DB::transaction(', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("['price_list_ids']", $service);
        $this->assertStringContainsString('$matches->count() !== 1', $service);
        $this->assertStringContainsString("'status' => 'accepted'", $service);
        $this->assertStringContainsString("'inventory_issue_id' => $issue->id", $service);
        $this->assertStringNotContainsString('->post(', $service);
    }

    public function test_admin_uses_search_select_and_single_action_without_manual_price_rows(): void
    {
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $review = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderReviewController.php'));

        $this->assertStringContainsString('<x-select-search', $view);
        $this->assertStringContainsString('Chấp nhận & tạo phiếu xuất nháp', $view);
        $this->assertStringContainsString('requests.accept-and-draft', $view);
        $this->assertStringNotContainsString('name="price_list_id"', $view);
        $this->assertStringNotContainsString('name="items[', $view);
        $this->assertStringContainsString("permission:website.settings.manage,admin", $routes);
        $this->assertStringContainsString("'in:rejected'", $review);
    }
}
