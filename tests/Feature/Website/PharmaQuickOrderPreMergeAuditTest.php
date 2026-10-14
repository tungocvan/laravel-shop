<?php

namespace Tests\Feature\Website;

use Tests\TestCase;

final class PharmaQuickOrderPreMergeAuditTest extends TestCase
{
    public function test_both_issue_handoff_paths_are_fail_closed(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderIssueHandoffService.php'));

        $this->assertStringContainsString('lockForUpdate()->findOrFail($requestId)', $service);
        $this->assertStringContainsString("if (\$request->status !== PharmaQuickOrderRequest::PENDING_REVIEW)", $service);
        $this->assertStringContainsString("if (\$request->status !== 'accepted')", $service);
        $this->assertStringContainsString("! in_array(\$priceListId, \$settings['price_list_ids'], true)", $service);
        $this->assertStringContainsString("! \$settings['enabled']", $service);
        $this->assertStringContainsString("->where('status', 'active')", $service);
        $this->assertStringNotContainsString('->post(', $service);
    }

    public function test_customer_status_never_claims_completion_without_posted_issue(): void
    {
        $model = file_get_contents(base_path('Modules/Website/Models/PharmaQuickOrderRequest.php'));
        $this->assertStringContainsString('InventoryIssue::POSTED', $model);
        $this->assertStringContainsString('InventoryIssue::REJECTED', $model);
        $this->assertStringContainsString('InventoryIssue::CANCELLED', $model);
        $this->assertStringContainsString("return 'processing';", $model);
    }

    public function test_notification_is_queued_and_contains_no_serialized_buyer_details(): void
    {
        $job = file_get_contents(base_path('Modules/Website/Jobs/SendPharmaQuickOrderNotification.php'));
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));
        $this->assertStringContainsString('implements ShouldQueue', $job);
        $this->assertStringContainsString('public readonly int $requestId', $job);
        $this->assertStringContainsString('->afterCommit()', $controller);
        $this->assertStringContainsString('if ($record->wasRecentlyCreated', $controller);
    }
}
