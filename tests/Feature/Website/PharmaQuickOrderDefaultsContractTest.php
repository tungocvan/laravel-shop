<?php

namespace Tests\Feature\Website;

use Modules\Website\Services\PharmaQuickOrderSettingsService;
use Tests\TestCase;

final class PharmaQuickOrderDefaultsContractTest extends TestCase
{
    public function test_quick_order_defaults_to_enabled_but_respects_explicit_disable(): void
    {
        $service = app(PharmaQuickOrderSettingsService::class);

        $this->assertTrue($service->normalize([])['enabled']);
        $this->assertTrue($service->normalize(['enabled' => true])['enabled']);
        $this->assertFalse($service->normalize(['enabled' => false])['enabled']);
        $this->assertSame([], $service->normalize([])['price_list_ids']);
    }

    public function test_store_uses_same_eligible_global_price_list_ids_in_all_filters(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/PharmaQuickOrderController.php'));
        $store = explode('public function store(', $controller, 2)[1] ?? '';

        $this->assertNotSame('', $store);
        $this->assertStringContainsString('$listIds = $priceLists->eligibleForCustomer(', $store);
        $this->assertStringContainsString('use ($listIds)', $store);
        $this->assertStringContainsString("->whereIn('exact_pli.price_list_id', $listIds)", $store);
        $this->assertStringNotContainsString('$globalListIds', $store);
    }
}
