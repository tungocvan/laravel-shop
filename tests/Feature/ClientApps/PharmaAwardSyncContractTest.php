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

    public function test_award_detail_route_and_safe_sync_form_are_wired(): void
    {
        $route = Route::getRoutes()->getByName('client.pharma.award-prices.show');
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('client.feature:pharma,award-prices', $route->gatherMiddleware());

        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-price-detail.blade.php'));
        $this->assertStringContainsString('client.pharma.award-prices.sync.preview', $detail);
        $this->assertStringContainsString('name="selected[]"', $detail);
        $this->assertStringContainsString("['status'] !== 'review'", $detail);
        $this->assertStringContainsString('GĐKLH / GPNK', $detail);
        foreach (['winningCode', 'tenCdtBmt', 'ngayBanHanhQuyetDinh', 'ngayDangTaiKqlcnt'] as $sourceField) {
            $this->assertStringContainsString($sourceField, $detail);
        }
        $this->assertStringContainsString("array_filter(\$item[\$field], 'is_scalar')", $detail);
        $this->assertStringNotContainsString('muasamcong.drug-pricing', $detail);
    }

    public function test_missing_name_or_unit_requires_review(): void
    {
        $sync = app(PublicAwardMedicineSync::class);
        $this->assertSame('review', $sync->inspect([
            'tenThuoc' => '', 'gdklh_GPNK' => '893100307624', 'donViTinh' => 'Viên',
        ])['status']);
        $this->assertSame('review', $sync->inspect([
            'tenThuoc' => 'Dibulaxan', 'gdklh_GPNK' => '893100307624', 'donViTinh' => '',
        ])['status']);
    }

    public function test_unverified_registration_is_provisional_and_not_written_to_official_registration(): void
    {
        $source = file_get_contents(base_path('Modules/Pharma/Services/PublicAwardMedicineSync.php'));
        $this->assertStringContainsString("'registration_number' => $this->registration($item) !== null ? $this->registrationRaw($item) : null", $source);
        $this->assertStringContainsString("'registration_raw_unverified'", $source);
        $this->assertStringContainsString("'requires_catalog_review'", $source);
        $this->assertStringContainsString("Medicine::PROFILE_NEEDS_REVIEW", $source);
        $this->assertStringContainsString("'provisional_source_record'", $source);
        $this->assertStringNotContainsString('Partial matches require manual review', $source);

        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-price-detail.blade.php'));
        $preview = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-sync-preview.blade.php'));
        $this->assertStringContainsString("!($match['registration_verified'] ?? false)", $detail);
        $this->assertStringContainsString("SĐK chưa xác minh", $preview);
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
        foreach (['Bộ lọc', 'Thấp nhất', 'Trung bình', 'Cao nhất', 'Xem chi tiết', 'Đồng bộ đã chọn', 'selected[]', 'form="award-select-form"', 'name="selected[]"', 'id="award-clear"'] as $label) {
            $this->assertStringContainsString($label, $blade);
        }
        $this->assertStringNotContainsString('Quan tâm', $blade);
        $this->assertStringNotContainsString('x-model=', $blade);
        $this->assertStringNotContainsString('x-teleport=', $blade);
        $this->assertStringContainsString('award-interactions.js', $blade);
        $this->assertStringContainsString('client.pharma.award-prices.show', $blade);
        $script = file_get_contents(base_path('public/demo/pharma-award/award-interactions.js'));
        $this->assertStringContainsString("input.value = '';", $script);
        $this->assertStringContainsString("rows.forEach(row", $script);
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
