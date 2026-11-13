<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class PharmaPriceListApprovalThreePricesContractTest extends TestCase
{
    public function test_approval_editor_submits_three_distinct_prices(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/price-list-approval-show.blade.php'));

        foreach (['company_sale_price', 'actual_receivable_price', 'invoice_price'] as $field) {
            $this->assertStringContainsString('name="'.$field.'"', $view);
            $this->assertStringContainsString('$item->'.$field, $view);
        }
        $this->assertStringContainsString('data-approval-money', $view);
        $this->assertStringContainsString('@if(!$selfApprovalBlocked)', $view);
    }

    public function test_approval_workflow_does_not_replace_receivable_and_invoice_with_sale_price(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListApprovalWorkflow.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaApplicationController.php'));

        $this->assertStringContainsString("'actual_receivable_price' => \$actualReceivablePrice", $service);
        $this->assertStringContainsString("'invoice_price' => \$invoicePrice", $service);
        $this->assertStringNotContainsString("'actual_receivable_price' => \$companySalePrice", $service);
        $this->assertStringContainsString("'old_actual_receivable_price' => \$oldReceivablePrice", $service);
        $this->assertStringContainsString("'old_invoice_price' => \$oldInvoicePrice", $service);
        $this->assertStringContainsString("'actual_receivable_price' => ['required', 'numeric', 'min:0']", $controller);
        $this->assertStringContainsString("'invoice_price' => ['required', 'numeric', 'min:0']", $controller);
        $this->assertStringContainsString('assertPendingAndIndependent($list, $approverUserId)', $service);
    }
}
