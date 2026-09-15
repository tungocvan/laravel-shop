<?php

namespace Modules\Pharma\Tests\Unit;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class PriceListCustomerContextContractTest extends TestCase
{
    #[Test]
    public function customer_price_list_has_manager_and_reusable_business_purpose_contract(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_160000_add_customer_ownership_and_purpose_to_price_lists.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceListPurpose.php'));
        $priceList = file_get_contents(base_path('Modules/Pharma/Models/PriceList.php'));

        $this->assertStringContainsString("'manager_user_id'", $migration);
        $this->assertStringContainsString("'purpose_id'", $migration);
        $this->assertStringContainsString('pharma_price_list_purposes', $migration);
        $this->assertStringContainsString('Chào giá vào bệnh viện', $migration);
        $this->assertStringContainsString('Chào giá Công ty Dược', $migration);
        $this->assertStringContainsString('Chào giá Nhà thuốc', $migration);
        $this->assertStringContainsString("protected \$table = 'pharma_price_list_purposes'", $model);
        $this->assertStringContainsString("'manager_user_id'", $priceList);
        $this->assertStringContainsString("'purpose_id'", $priceList);
        $this->assertStringContainsString('function purpose()', $priceList);
        $this->assertStringContainsString('function manager()', $priceList);
    }
}
