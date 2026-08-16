<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Modules\Muasamcong\Models\PricingWishlist;
use Modules\Muasamcong\Services\PricingWishlistService;
use Tests\TestCase;

class MuasamcongPricingWishlistTest extends TestCase
{
    use RefreshDatabase;

    public function test_wishlist_is_scoped_per_user_and_toggles_without_duplicates(): void
    {
        $service = app(PricingWishlistService::class);
        $item = [
            'id' => '1d70016e-2d73-437c-ad5f-f4af12eb36ef',
            'tenThuoc' => 'Gourcuff-2,5',
            'tenHoatChat' => 'Alfuzosin hydrochlorid',
            'nongDo' => '2,5mg',
            'nhomThuoc' => 'Nhóm 2',
            'maTbmt' => 'IB2600029438',
        ];

        $this->assertTrue($service->toggle(10, 'Gourcuff-2,5', $item));
        $this->assertDatabaseCount('muasamcong_pricing_wishlists', 1);

        $saved = PricingWishlist::query()->firstOrFail();
        $this->assertSame(10, $saved->user_id);
        $this->assertSame('Gourcuff-2,5', $saved->medicine_name);
        $this->assertSame([$item['id']], $service->sourceIdsForUser(10, [$item]));
        $this->assertSame([], $service->sourceIdsForUser(11, [$item]));

        $this->assertFalse($service->toggle(10, 'Gourcuff-2,5', $item));
        $this->assertDatabaseCount('muasamcong_pricing_wishlists', 0);
    }
}
