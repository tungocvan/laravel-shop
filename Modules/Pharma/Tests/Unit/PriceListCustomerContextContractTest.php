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
        $createComponent = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $createView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        $this->assertStringContainsString("'partnerId' => ['nullable', 'integer', 'exists:partners,id']", $createComponent);
        $this->assertStringNotContainsString("CUSTOMER_SOURCE_PARTNER ? 'required' : 'nullable'", $createComponent);
        $this->assertStringContainsString('Khách hàng <span class="text-xs font-normal text-slate-400">(không bắt buộc)</span>', $createView);
        $this->assertStringContainsString('Để trống = bảng giá chung cho nhiều khách hàng', $createView);
        $this->assertStringContainsString('Không chọn · áp dụng chung nhiều khách hàng', $createView);
        $manager = file_get_contents(base_path('Modules/Pharma/Services/PriceListManager.php'));
        $this->assertStringContainsString('if ($partnerId !== null)', $manager);
        $this->assertStringContainsString('if ($officialFacilityId !== null', $manager);
        $this->assertStringNotContainsString('if (! $officialFacilityId || ! OfficialSourceFacility::query()', $manager);
    }
}
