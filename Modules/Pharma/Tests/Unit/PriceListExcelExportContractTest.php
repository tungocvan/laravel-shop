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
    public function export_catalog_covers_full_pharma_master_data_and_commercial_evidence(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));
        $compactController = preg_replace('/\s+/', '', $controller);

        foreach ([
            'medicine_code', 'medicine_name', 'registration_number', 'registration_number_raw', 'registration_number_primary', 'sku', 'package_code', 'gtin', 'barcode',
            'active_ingredients', 'strength', 'dosage_form', 'route_of_administration', 'unit', 'therapeutic_group', 'circular_group', 'circular_order_number', 'shelf_life', 'shelf_life_months', 'is_special_control',
            'registered_company', 'manufacturing_company', 'manufacturing_country', 'visa_validity_date', 'gmp_certification_date',
            'presentation_text', 'base_unit', 'content_value', 'content_uom', 'package', 'outer_package_type', 'inner_package_type', 'outer_quantity', 'inner_quantity', 'base_quantity', 'container_volume', 'container_volume_uom', 'is_orderable', 'is_inventory_unit',
            'declared_price', 'bid_price', 'bid_quantity', 'bid_decision', 'bid_date', 'bid_contractor', 'bid_investor', 'bid_unit', 'bid_source',
            'company_sale_price', 'discount_percent', 'actual_receivable_price', 'invoice_price', 'partner', 'effective', 'status', 'note',
        ] as $column) {
            $this->assertStringContainsString("'{$column}'", $service);
            $this->assertStringContainsString("'{$column}'=>", $compactController);
        }

        $this->assertStringContainsString('public const GROUPS', $service);
        $this->assertStringContainsString('public const DEFAULT_SELECTED', $service);
        $this->assertStringContainsString("'export_profile_id'", $controller);
        $this->assertStringContainsString('PriceListExportProfileService $profiles', $controller);
        foreach (['medicine', 'variant', 'package', 'bidEvidence'] as $relation) {
            $this->assertStringContainsString("'{$relation}'", $controller);
        }
        $this->assertStringContainsString('$selected->isNotEmpty()', $controller);
        $this->assertStringContainsString('new Drawing()', $controller);
    }
}
