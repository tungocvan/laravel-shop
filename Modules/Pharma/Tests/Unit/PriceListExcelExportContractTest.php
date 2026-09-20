<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListExcelExportContractTest extends TestCase
{
    #[Test]
    public function price_list_detail_exposes_designer_and_requested_table_layout(): void
    {
        $view=file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));$config=file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/export-configurator.blade.php'));
        foreach(['submitPriceListExport()','name="items[]"','Mẫu bảng báo giá','pharma-export-profile-id','Nhóm thuốc','KQ trúng thầu'] as$needle)$this->assertStringContainsString($needle,$view);
        $index=file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/index.blade.php'));$this->assertStringContainsString("@livewire('pharma.price-list.export-configurator')",$index);$this->assertStringNotContainsString("@livewire('pharma.price-list.export-configurator')",$view);
        $this->assertStringNotContainsString('>Giá thu<',$view);$this->assertStringNotContainsString('>Giá xuất HĐ<',$view);
        foreach(['Excel Designer','Bố cục xuất Bảng giá','Cột dữ liệu','Trang in'] as$needle)$this->assertStringContainsString($needle,$config);$this->assertStringNotContainsString('localStorage',$config);
    }

    #[Test]
    public function export_catalog_covers_full_pharma_master_data_and_commercial_evidence(): void
    {
        $controller=file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));$service=file_get_contents(base_path('Modules/Pharma/Services/PriceListExportProfileService.php'));$layout=file_get_contents(base_path('Modules/Pharma/Services/PriceListExcelDocumentLayout.php'));$compact=preg_replace('/\s+/','',$controller);
        foreach(['medicine_code','medicine_name','registration_number','registration_number_raw','registration_number_primary','sku','package_code','gtin','barcode','active_ingredients','strength','dosage_form','route_of_administration','unit','therapeutic_group','circular_group','circular_order_number','shelf_life','shelf_life_months','is_special_control','registered_company','manufacturing_company','manufacturing_country','visa_validity_date','gmp_certification_date','presentation_text','base_unit','content_value','content_uom','package','outer_package_type','inner_package_type','outer_quantity','inner_quantity','base_quantity','container_volume','container_volume_uom','is_orderable','is_inventory_unit','declared_price','bid_price','bid_quantity','bid_decision','bid_date','bid_contractor','bid_investor','bid_unit','bid_source','company_sale_price','discount_percent','actual_receivable_price','invoice_price','partner','manager','effective','status','note']as$column){$this->assertStringContainsString("'{$column}'",$service);$this->assertStringContainsString("'{$column}'=>",$compact);}
        $this->assertStringContainsString('public const GROUPS',$service);$this->assertStringContainsString('public const DEFAULT_SELECTED',$service);$this->assertStringContainsString("'export_profile_id'",$controller);$this->assertStringContainsString('PriceListExportProfileService $profiles',$controller);foreach(['medicine','variant','package','bidEvidence','officialFacility','manager']as$relation)$this->assertStringContainsString("'{$relation}'",$controller);$this->assertStringContainsString('$selected->isNotEmpty()',$controller);$this->assertStringContainsString("customer_source==='official_facility'",$compact);$this->assertStringContainsString("'manager'=>\$priceList->manager?->name",$controller);$this->assertStringContainsString('\$customerLabel=',$controller);$this->assertStringContainsString("header(\$sheet,\$profile,count(\$columns),\$customerLabel)",$compact);$this->assertStringContainsString("\$recipient = trim((string) (\$hf['recipient'] ?? ''));",$layout);$this->assertStringContainsString('\$fallbackRecipient',$layout);
        $this->assertStringContainsString('PriceListExcelDocumentLayout $layout',$controller);$this->assertStringContainsString('new Drawing',$layout);$this->assertStringContainsString("\$profile['logo_path']",$layout);$this->assertStringContainsString("\$profile['signature_path']",$layout);
    }
}
