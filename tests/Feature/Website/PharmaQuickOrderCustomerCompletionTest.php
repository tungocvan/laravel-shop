<?php

namespace Tests\Feature\Website;

use Modules\Pharma\Models\InventoryIssue;
use Modules\Website\Models\PharmaQuickOrderRequest;
use Tests\TestCase;

final class PharmaQuickOrderCustomerCompletionTest extends TestCase
{
    public function test_customer_completion_follows_canonical_issue_status_and_reversal(): void
    {
        $request = new PharmaQuickOrderRequest(['status' => 'accepted', 'inventory_issue_id' => 20]);
        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::DRAFT]));
        $this->assertSame('processing', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::APPROVED]));
        $this->assertSame('processing', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::POSTED]));
        $this->assertSame('completed', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::DRAFT]));
        $this->assertSame('processing', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::REJECTED]));
        $this->assertSame('issue_rejected', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::CANCELLED]));
        $this->assertSame('issue_cancelled', $request->customer_order_status);

        $request->setRelation('inventoryIssue', new InventoryIssue(['status' => InventoryIssue::DRAFT]));
        $this->assertSame('processing', $request->customer_order_status);

        $request->setRelation('inventoryIssue', null);
        $this->assertSame('processing', $request->customer_order_status);
        $request->status = 'rejected';
        $this->assertSame('rejected', $request->customer_order_status);
    }

    public function test_customer_views_are_status_driven_and_eager_loaded(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/AccountController.php'));
        $index = file_get_contents(base_path('Modules/Website/resources/views/account/orders/index.blade.php'));
        $detail = file_get_contents(base_path('Modules/Website/resources/views/account/orders/pharma-request.blade.php'));
        $this->assertStringContainsString("->with('inventoryIssue:id,status')", $controller);
        $this->assertStringContainsString("->with('inventoryIssue:id,status,posted_at')", $controller);
        $this->assertStringContainsString('customer_order_status', $index);
        $this->assertStringContainsString('Hoàn tất · Đã ghi sổ', $index);
        $this->assertStringContainsString('Phiếu xuất bị từ chối', $index);
        $this->assertStringContainsString('customer_order_status', $detail);
        $this->assertStringContainsString('Đã ghi sổ xuất kho thành công', $detail);
        $this->assertStringContainsString('Phiếu xuất kho đã bị từ chối.', $detail);
    }
}
