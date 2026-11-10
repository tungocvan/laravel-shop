<?php

namespace Tests\Feature\ClientApps;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PharmaMedicineExcelWinningQuantityContractTest extends TestCase
{
    #[Test]
    public function winning_quantity_is_distinct_from_internal_allocated_quantity(): void
    {
        $related = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelRelatedDataService.php'));
        $profile = file_get_contents(base_path('Modules/Pharma/Services/MedicineExcelProfileService.php'));

        $this->assertStringContainsString("'winning_quantity' => 'Số lượng trúng thầu'", $related);
        $this->assertStringContainsString("'winning_quantity' => 'Số lượng trúng thầu'", $profile);
        $this->assertStringContainsString("\$values['winning_quantity'] = \$this->display(\$award->quantity)", $related);
        $this->assertStringContainsString("\$values['winning_quantity'] = \$this->display(\$snapshot['soLuong'] ?? null)", $related);
        $this->assertStringContainsString("\$values['allocated_quantity'] = \$allocation > 0", $related);
        $this->assertStringContainsString('Public source has no verified allocation or contract period', $related);
        $this->assertStringNotContainsString("\$values['contract_period_text'] = \$this->display(\$snapshot['ngayBanHanhQuyetDinh']", $related);
    }

    #[Test]
    public function admin_and_client_workbooks_support_numeric_winning_quantity(): void
    {
        $client = file_get_contents(base_path('Modules/Pharma/Services/MedicineConfiguredExcelExporter.php'));
        $admin = file_get_contents(base_path('Modules/Pharma/Http/Controllers/MedicineExcelController.php'));

        $this->assertStringContainsString("'winning_price', 'winning_quantity', 'allocated_quantity'", $client);
        $this->assertStringContainsString("'winning_price', 'winning_quantity', 'allocated_quantity'", $admin);
    }
}
