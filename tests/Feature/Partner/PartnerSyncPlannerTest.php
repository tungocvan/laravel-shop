<?php

namespace Tests\Feature\Partner;

use Modules\Partner\Data\ExternalPartnerData;
use Modules\Partner\Models\Partner;
use Modules\Partner\Services\PartnerSyncPlanner;
use Tests\TestCase;

class PartnerSyncPlannerTest extends TestCase
{
    public function test_new_partner_values_are_selected_for_reviewed_creation(): void
    {
        $external = new ExternalPartnerData(
            source: 'masothue',
            externalId: '0301234567',
            name: 'Công ty ABC',
            taxCode: '0301234567',
            address: 'TP.HCM',
        );

        $plan = app(PartnerSyncPlanner::class)->plan(null, $external);

        $this->assertSame('new_value', $plan['tax_code']['state']);
        $this->assertSame('new_value', $plan['name']['state']);
        $this->assertTrue($plan['address']['selected']);
    }

    public function test_existing_non_empty_local_value_is_a_conflict_and_not_selected(): void
    {
        $partner = new Partner([
            'tax_code' => '0301234567',
            'name' => 'Tên nội bộ',
            'address' => 'Địa chỉ nội bộ',
        ]);

        $external = new ExternalPartnerData(
            source: 'masothue',
            externalId: '0301234567',
            name: 'Tên pháp lý',
            taxCode: '0301234567',
            address: null,
        );

        $plan = app(PartnerSyncPlanner::class)->plan($partner, $external);

        $this->assertSame('same', $plan['tax_code']['state']);
        $this->assertSame('local_differs', $plan['name']['state']);
        $this->assertFalse($plan['name']['selected']);
        $this->assertSame('source_missing', $plan['address']['state']);
        $this->assertFalse($plan['address']['selected']);
    }
}
