<?php

namespace Tests\Feature\ClientApps;

use Tests\TestCase;

final class LoanManagementContractDetailTabsContractTest extends TestCase
{
    public function test_operations_have_five_accessible_native_tabs_and_scoped_panels(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/partials/contract-operations.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/loans/contract-detail.blade.php'));

        $this->assertStringContainsString('role="tablist"', $view);
        $this->assertStringContainsString('role="tab"', $view);
        $this->assertStringContainsString('role="tabpanel"', $view);
        $this->assertStringContainsString('aria-controls=', $view);
        $this->assertStringContainsString('aria-labelledby=', $view);
        $this->assertStringContainsString('data-loan-tab=', $view);
        $this->assertStringContainsString('data-loan-panel=', $view);
        foreach (['loan-disbursement', 'loan-schedules', 'loan-payments', 'loan-reminders', 'loan-insurance'] as $id) {
            $this->assertStringContainsString("'".$id."'", $view);
            $this->assertStringContainsString('id="'.$id.'" role="tabpanel"', $view);
        }
        $this->assertStringContainsString("event.key==='ArrowRight'", $view);
        $this->assertStringContainsString("event.key==='Home'", $view);
        $this->assertStringContainsString("event.key==='End'", $view);
        $this->assertStringContainsString('window.location.hash', $view);
        $this->assertStringContainsString('sessionStorage.setItem(key', $view);
        $this->assertStringContainsString('root.dataset.loanContractId', $view);
        $this->assertStringContainsString('data-loan-contract-id=', $detail);
        $this->assertStringContainsString('[hidden]{display:none!important}', $detail);
        $this->assertStringNotContainsString('x-data=', $view);
    }
}
