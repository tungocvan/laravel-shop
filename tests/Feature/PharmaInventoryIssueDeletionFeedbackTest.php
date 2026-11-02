<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaInventoryIssueDeletionFeedbackTest extends TestCase
{
    public function test_issue_list_surfaces_validation_errors_and_success_messages(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/inventory/documents.blade.php'));

        $this->assertStringContainsString('@if($errors->any())', $view);
        $this->assertStringContainsString('$errors->all()', $view);
        $this->assertStringContainsString('role="alert"', $view);
        $this->assertStringContainsString("session('success')", $view);
        $this->assertStringContainsString("route('admin.pharma.inventory.issues.destroy',\$doc)", $view);
    }

    public function test_issue_delete_retains_audit_guard_and_cleans_operational_children(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $start = strpos($controller, 'public function destroyIssue(');
        $end = strpos($controller, 'public function exportIssues(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($controller, $start, $end - $start);

        $this->assertStringContainsString('lockForUpdate()', $method);
        $this->assertStringContainsString('[InventoryIssue::DRAFT,InventoryIssue::REJECTED]', $method);
        $this->assertStringContainsString('InventoryIssueCommission::query()', $method);
        $this->assertStringContainsString('$locked->deferredSupplies()->delete()', $method);
        $this->assertStringContainsString('$locked->items()->delete()', $method);
        $this->assertStringContainsString('$locked->delete()', $method);
    }
}
