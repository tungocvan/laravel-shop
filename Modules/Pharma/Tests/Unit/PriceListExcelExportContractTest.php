<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExcelExportContractTest extends TestCase
{
    #[Test]
    public function price_list_detail_exposes_column_configuration_and_selection_scope(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));
        $config = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/partials/export-config.blade.php'));

        $this->assertStringContainsString('Cấu hình cột', $view);
        $this->assertStringContainsString('submitPriceListExport()', $view);
        $this->assertStringContainsString('name="items[]"', $view);
        $this->assertStringContainsString('Cấu hình cột xuất Excel', $config);
        $this->assertStringContainsString("name='columns[]'", $config);
        $this->assertStringContainsString('localStorage', $config);
    }

    #[Test]
    public function export_controller_supports_commercial_and_bid_evidence_columns(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));

        foreach (['medicine_code','sku','declared_price','bid_price','bid_quantity','bid_decision','bid_date','bid_contractor','bid_source','company_sale_price','discount_percent','actual_receivable_price','invoice_price'] as $column) {
            $this->assertStringContainsString("'{$column}'", $controller);
        }
        $this->assertStringContainsString("'columns.*'", $controller);
        $this->assertStringContainsString("with(['medicine','variant','package','bidEvidence'])", $controller);
        $this->assertStringContainsString('if ($selected->isNotEmpty())', $controller);
    }
}
