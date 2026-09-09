<?php

namespace Tests\Feature\ClientPortal;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoicesDashboardSelectedMonthRegressionTest extends TestCase
{
    #[Test]
    public function dashboard_uses_selected_month_for_monthly_kpis_and_comparisons(): void
    {
        $adapter = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Services/ClientInvoiceWorkspaceService.php'));

        $this->assertStringContainsString("\$sameMonth = \$month ?? (int) now()->format('m');", $adapter);
        $this->assertStringContainsString('$sameMonthFilters = $this->periodFilters($year, $sameMonth);', $adapter);
        $this->assertStringContainsString("'currentMonthLabel' => sprintf('Tháng %02d/%d', \$sameMonth, \$year)", $adapter);
        $this->assertStringContainsString('$previousMonthDate = now()->setDate($year, $sameMonth, 1)->subMonth();', $adapter);
        $this->assertStringContainsString('$this->periodFilters($previousYear, $sameMonth)', $adapter);
    }
}
