<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaQuickOrderPriceListService;
use Tests\TestCase;

class PharmaQuickOrderPriceListBoundaryTest extends TestCase
{
    public function test_empty_admin_allowlist_fails_closed(): void
    {
        $query = app(PharmaQuickOrderPriceListService::class)
            ->eligibleForCustomer(10, 20, [], '2026-10-14');

        $this->assertStringContainsString('0 = 1', $query->toSql());
    }

    public function test_eligible_query_requires_active_list_and_customer_scope(): void
    {
        $query = app(PharmaQuickOrderPriceListService::class)
            ->eligibleForCustomer(10, 20, [3, 3, -4], '2026-10-14');

        $sql = $query->toSql();

        $this->assertStringContainsString('pharma_price_lists', $sql);
        $this->assertStringContainsString('effective_from', $sql);
        $this->assertStringContainsString('effective_to', $sql);
        $this->assertStringContainsString('partner_id', $sql);
        $this->assertStringContainsString('global', implode(' ', $query->getBindings()));
        $this->assertContains(10, $query->getBindings());
        $this->assertNotContains(-4, $query->getBindings());
    }
}
