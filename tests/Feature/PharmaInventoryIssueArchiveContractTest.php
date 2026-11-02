<?php

namespace Tests\Feature;

use Tests\TestCase;

class PharmaInventoryIssueArchiveContractTest extends TestCase
{
    public function test_draft_or_rejected_issue_with_commission_is_archived_without_erasing_ledger(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $start = strpos($source, 'public function destroyIssue(');
        $end = strpos($source, 'public function exportIssues(', $start);
        $this->assertNotFalse($start);
        $this->assertNotFalse($end);
        $method = substr($source, $start, $end - $start);

        $this->assertStringContainsString('[InventoryIssue::DRAFT,InventoryIssue::REJECTED]', $method);
        $this->assertStringContainsString('InventoryIssueCommission::query()', $method);
        $this->assertStringContainsString("'archived_at'=>now()", $method);
        $this->assertStringContainsString("'archived_by'=>auth('admin')->id()", $method);
        $this->assertStringContainsString('return true;', $method);
        $this->assertStringContainsString('$locked->deferredSupplies()->delete()', $method);
        $this->assertStringContainsString('$locked->items()->delete()', $method);
        $this->assertStringNotContainsString('commissions()->delete()', $method);
    }

    public function test_archived_issues_are_hidden_from_admin_and_client_lists(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/InventoryController.php'));
        $workspace = file_get_contents(base_path('Modules/Pharma/Services/UserInventoryIssueWorkspace.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_02_000001_add_archived_at_to_pharma_inventory_issues.php'));

        $this->assertGreaterThanOrEqual(2, substr_count($controller, "->whereNull('archived_at')"));
        $this->assertStringContainsString("InventoryIssue::query()->whereNull('archived_at')", $workspace);
        $this->assertStringContainsString("'archived_at'", $migration);
    }
}
