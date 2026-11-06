<?php

namespace Tests\Feature\ClientApps;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Modules\Pharma\Services\PublicAwardMedicineSync;
use Modules\Pharma\Services\PublicAwardPriceLookup;
use Tests\TestCase;

class PharmaAwardSyncContractTest extends TestCase
{
    public function test_sync_routes_are_post_only_and_guarded_by_pharma_feature(): void
    {
        foreach (['client.pharma.award-prices.sync.preview', 'client.pharma.award-prices.sync.store'] as $name) {
            $route = Route::getRoutes()->getByName($name);
            $this->assertNotNull($route);
            $this->assertContains('POST', $route->methods());
            $this->assertNotContains('GET', $route->methods());
            $this->assertContains('client.feature:pharma,award-prices', $route->gatherMiddleware());
        }
    }

    public function test_missing_registration_is_review_only(): void
    {
        $sync = app(PublicAwardMedicineSync::class);
        $this->assertSame('review', $sync->inspect([
            'tenThuoc' => 'Unafen', 'gdklh_GPNK' => '', 'donViTinh' => 'Chai',
        ])['status']);
        $this->assertSame('review', $sync->inspect([
            'tenThuoc' => 'Unafen', 'gdklh_GPNK' => 'GPNK-12345', 'donViTinh' => 'Chai',
        ])['status']);
    }

    public function test_award_search_paginates_and_refreshes_only_its_cache(): void
    {
        Cache::flush();
        Http::fake(['muasamcong.mof.gov.vn/*' => Http::response([
            'page' => ['content' => [['id' => 'a1', 'tenThuoc' => 'Unafen']], 'totalElements' => 25],
        ])]);
        $lookup = app(PublicAwardPriceLookup::class);
        $this->assertSame(25, $lookup->search('unafen', 2)['total']);
        $lookup->search('unafen', 2, true);
        Http::assertSentCount(2);
        Http::assertSent(fn ($request) => $request->data()[0]['pageNumber'] === 1);
    }

    public function test_ui_exposes_details_bulk_sync_stats_and_filters_without_wishlist(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-prices.blade.php'));
        foreach (['Bộ lọc', 'Thấp nhất', 'Trung bình', 'Cao nhất', 'Xem chi tiết', 'Đồng bộ đã chọn', 'selected[]'] as $label) {
            $this->assertStringContainsString($label, $blade);
        }
        $this->assertStringNotContainsString('Quan tâm', $blade);
    }

    public function test_sync_never_overwrites_medicine_prices_or_stock(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Services/PublicAwardMedicineSync.php'));
        $this->assertStringContainsString('MedicineSource::query()->updateOrCreate', $source);
        $this->assertStringContainsString("'source_record_key' => \$sourceId", $source);
        $this->assertStringNotContainsString("'declared_price' =>", $source);
        $this->assertStringNotContainsString("'selling_price' =>", $source);
        $this->assertStringNotContainsString("'stock' =>", $source);
    }
}
