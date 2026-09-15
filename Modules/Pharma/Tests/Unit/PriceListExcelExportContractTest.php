<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExcelExportContractTest extends TestCase
{
    #[Test]
    public function price_list_detail_exposes_designer_and_requested_table_layout(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));
        $config = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));

        $this->assertStringContainsString('submitPriceListExport()', $view);
        $this->assertStringContainsString('name="items[]"', $view);
        $this->assertStringContainsString("@livewire('pharma.price-list.export-configurator')", $view);
        $this->assertStringContainsString('Nhóm thuốc', $view);
        $this->assertStringContainsString('KQ trúng thầu', $view);
        $this->assertStringNotContainsString('>Giá thu<', $view);
        $this->assertStringNotContainsString('>Giá xuất HĐ<', $view);
        $this->assertStringContainsString('Excel Designer', $config);
        $this->assertStringContainsString('Bố cục xuất Bảng giá', $config);
        $this->assertStringContainsString('Cột dữ liệu', $config);
        $this->assertStringContainsString('Trang in', $config);
        $this->assertStringNotContainsString('localStorage', $config);
    }

    #[Test]
    public function export_controller_supports_persistent_profile_commercial_and_bid_evidence_columns(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));

        foreach (['medicine_code', 'sku', 'declared_price', 'bid_price', 'bid_quantity', 'bid_decision', 'bid_date', 'bid_contractor', 'bid_source', 'company_sale_price', 'discount_percent', 'actual_receivable_price', 'invoice_price'] as $column) {
            $this->assertStringContainsString("'{$column}'", $service);
        }

        $this->assertStringContainsString("'export_profile_id'", $controller);
        $this->assertStringContainsString('PriceListExportProfileService $profiles', $controller);
        $this->assertStringContainsString("with(['medicine','variant','package','bidEvidence'])", $controller);
        $this->assertStringContainsString('if($selected->isNotEmpty())', $controller);
        $this->assertStringContainsString('new Drawing()', $controller);
    }
}
