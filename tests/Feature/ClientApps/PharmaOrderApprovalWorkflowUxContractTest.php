<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaOrderApprovalWorkflowUxContractTest extends TestCase
{
    public function test_draft_submit_action_is_permission_and_owner_scoped(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issues.blade.php'));

        $this->assertStringContainsString("'canSubmitOrders' => \$registry->userCan(\$user, 'client.pharma.orders.submit')", $controller);
        $this->assertStringContainsString("InventoryIssue::DRAFT && (\$canSubmitOrders ?? false)", $view);
        $this->assertStringContainsString('in_array($currentUserId, [(int) $issue->created_by, (int) $issue->manager_user_id], true)', $view);
        $this->assertStringContainsString("route('client.pharma.orders.submit',\$issue)", $view);
        $this->assertStringContainsString('Gửi duyệt không trừ tồn kho', $view);
        $this->assertStringContainsString('Chờ duyệt · Xem điều kiện', $view);
        $this->assertStringContainsString("InventoryIssue::APPROVED", $view);
    }
}
