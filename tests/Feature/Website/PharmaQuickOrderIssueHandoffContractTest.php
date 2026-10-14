<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderIssueHandoffContractTest extends TestCase
{
    public function test_handoff_requires_admin_permission_and_preserves_draft_lifecycle(): void
    {
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderIssueHandoffService.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));

        $this->assertStringContainsString("requests/{quickOrderRequest}/inventory-draft", $routes);
        $this->assertStringContainsString("permission:website.settings.manage,admin", $routes);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString('inventory_issue_id', $service);
        $this->assertStringContainsString('UserOrderAuthoringService', $service);
        $this->assertStringContainsString('createDraft(', $service);
        $this->assertStringNotContainsString('->submit(', $service);
        $this->assertStringNotContainsString('->post(', $service);
        $this->assertStringContainsString('Chấp nhận & tạo phiếu xuất nháp', $view);
        $this->assertStringContainsString('requests.accept-and-draft', $view);
        $this->assertStringContainsString('name="recipient_partner_id"', $view);
        $this->assertStringContainsString('name="manager_user_id"', $view);
    }
}
