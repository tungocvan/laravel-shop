<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

class PharmaPriceListManualLotsContractTest extends TestCase
{
    public function test_price_list_approval_and_lot_posting_use_shared_pharma_services(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));
        $approval = file_get_contents(base_path('Modules/Pharma/Services/UserOrderApprovalService.php'));
        $lots = file_get_contents(base_path('Modules/Pharma/Services/BidIssueManualLotPostingService.php'));
        $inventory = file_get_contents(base_path('Modules/Pharma/Services/InventoryService.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/inventory-issue-show.blade.php'));

        $this->assertStringContainsString('approvePartially(', $controller);
        $this->assertStringContainsString('function approvePartially(', $approval);
        $this->assertStringContainsString('InventoryIssueDeferredSupply::updateOrCreate(', $approval);
        $this->assertStringContainsString('function post(', $lots);
        $this->assertStringContainsString("! in_array(\$visible->issue_source, ['bid', 'normal', 'price_list'], true)", $controller);
        $this->assertStringContainsString("route('client.pharma.orders.show', \$issue)", $controller);
        $this->assertStringContainsString('postIssue($locked->fresh(', $lots);
        $this->assertStringContainsString("return redirect()->route('client.pharma.orders.lots', \$issue)", $controller);
        $this->assertStringContainsString("route('client.pharma.orders.lots', \$issue)", $view);
        $this->assertStringContainsString('($item->batch_number && $item->expiry_date)', $inventory);
    }

    public function test_fefo_suggestions_share_lot_capacity_across_split_rows(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/order-lots.blade.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/BidIssueManualLotPostingService.php'));

        $this->assertStringContainsString('$fefoRemainingByLot = []', $view);
        $this->assertStringContainsString('$lotRemaining = $fefoRemainingByLot[$lot->id]', $view);
        $this->assertStringContainsString('$fefoRemainingByLot[$lot->id] = $lotRemaining - $suggested', $view);
        $this->assertStringContainsString('data-balance-id="{{ $lot->id }}"', $view);
        $this->assertStringContainsString('const lotTotals = new Map()', $view);
        $this->assertStringContainsString('lotTotals.get(input.dataset.balanceId)', $view);
        $this->assertStringContainsString('rowsValid && lotsWithinStock && total === required', $view);
        $this->assertStringContainsString('$lotTotals[$balanceId]', $service);
        $this->assertStringContainsString('lockForUpdate()', $service);
    }

}
