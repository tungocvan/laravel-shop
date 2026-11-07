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

    public function test_wishlist_detail_is_owner_scoped_and_reuses_verified_sync_preview(): void
    {
        $route = Route::getRoutes()->getByName('client.pharma.award-prices.wishlist.show');
        $this->assertNotNull($route);
        $this->assertContains('GET', $route->methods());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,award-prices', $route->gatherMiddleware());
        $this->assertSame('apps/pharma/award-prices/wishlist/{sourceId}', $route->uri());

        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaAwardPriceController.php'));
        $wishlist = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-wishlist.blade.php'));
        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-price-detail.blade.php'));

        $this->assertStringContainsString('public function showWishlist(', $controller);
        $this->assertStringContainsString("->where('user_id', (int) \$request->user('web')->id)", $controller);
        $this->assertStringContainsString("->where('source_id', \$sourceId)", $controller);
        $this->assertStringContainsString('->firstOrFail()', $controller);
        $this->assertStringContainsString("'item' => \$item", $controller);
        $this->assertStringContainsString("'fromWishlist' => true", $controller);
        $this->assertStringContainsString("'fromWishlist' => false", $controller);
        $this->assertStringContainsString('client.pharma.award-prices.wishlist.show', $wishlist);
        $this->assertStringContainsString('aria-label="Xem chi tiết', $wishlist);
        $this->assertStringNotContainsString('>Tra cứu lại</a>', $wishlist);
        $this->assertStringContainsString('client.pharma.award-prices.wishlist', $detail);
        $this->assertStringContainsString('bản ghi đã lưu trong danh sách quan tâm', $detail);
        $this->assertStringContainsString('client.pharma.award-prices.sync.preview', $detail);
        $this->assertStringContainsString('name="selected[]"', $detail);

        // Sync preview re-fetches the source and refuses stale or missing records.
        $this->assertStringContainsString('\$items = \$this->selectedItems(\$lookup, \$input)', $controller);
        $this->assertStringContainsString("Bản ghi đã chọn không còn trong trang dữ liệu nguồn.", $controller);
        $this->assertStringContainsString("if (\$match['status'] === 'review')", $controller);
        $this->assertStringNotContainsString('Modules\\Muasamcong', $controller);
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
        $this->assertStringContainsString("'registration_number' => \$this->registration(\$item) !== null ? \$this->registrationRaw(\$item) : null", $source);
        $this->assertStringContainsString("'registration_raw_unverified'", $source);
        $this->assertStringContainsString("'requires_catalog_review'", $source);
        $this->assertStringContainsString("Medicine::PROFILE_NEEDS_REVIEW", $source);
        $this->assertStringContainsString("'provisional_source_record'", $source);
        $this->assertStringNotContainsString('Partial matches require manual review', $source);

        $detail = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-price-detail.blade.php'));
        $preview = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-sync-preview.blade.php'));
        $this->assertStringContainsString("!(\$match['registration_verified'] ?? false)", $detail);
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

    public function test_award_ui_has_page_local_filters_independent_wishlist_and_sync(): void
    {
        $blade = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-prices.blade.php'));
        foreach ([
            'Bộ lọc kết quả', 'Nơi trúng thầu', 'Nhóm thuốc', 'Tên thuốc, hoạt chất, hàm lượng',
            'award-local-search', 'award-group-filter',
            'award-reset-filters', 'award-filter-count', 'Đồng bộ đã chọn',
            'form="award-select-form"', 'name="selected[]"', 'id="award-clear"',
            'client.pharma.award-prices.show', 'client.pharma.award-prices.wishlist.toggle',
            'tenCdtBmt', 'nhomThuoc', 'aria-pressed',
        ] as $label) {
            $this->assertStringContainsString($label, $blade);
        }
        foreach (['Thấp nhất', 'Trung bình', 'Cao nhất', 'Giá từ (đ)', 'Giá đến (đ)', 'award-price-min', 'award-price-max', 'ĐVT (lọc trong trang)', '<th class="px-3 py-3">TBMT</th>'] as $removed) {
            $this->assertStringNotContainsString($removed, $blade);
        }
        $this->assertStringContainsString("@section('hide-application-header', true)", $blade);
        $this->assertStringContainsString("@section('hide-mobile-navigation', true)", $blade);
        $script = file_get_contents(base_path('public/demo/pharma-award/award-interactions.js'));
        $this->assertStringContainsString('filterRows()', $script);
        $this->assertStringContainsString('visibleSelections()', $script);
        $this->assertStringContainsString('row.hidden = !matches', $script);
        $this->assertStringContainsString("row.style.display = matches ? '' : 'none'", $script);
        $this->assertStringContainsString('data.group === selectedGroup', $script);
        $this->assertStringContainsString('row.closest(\'tr\')?.style.display', $script);
        $this->assertStringContainsString('v=20261107-2', $blade);
        $this->assertStringContainsString('normalizedGroup', $blade);
        $this->assertStringContainsString('preg_match(', $blade);
        $this->assertStringContainsString('row.checked', $script);
        $this->assertStringNotContainsString('fetch(', $script);
    }

    public function test_award_wishlist_is_independent_persistent_and_authorized(): void
    {
        $list = Route::getRoutes()->getByName('client.pharma.award-prices.wishlist');
        $this->assertNotNull($list);
        $this->assertContains('GET', $list->methods());
        $this->assertContains('auth:web', $list->gatherMiddleware());

        $route = Route::getRoutes()->getByName('client.pharma.award-prices.wishlist.toggle');
        $this->assertNotNull($route);
        $this->assertContains('POST', $route->methods());
        $this->assertNotContains('GET', $route->methods());
        $this->assertContains('auth:web', $route->gatherMiddleware());
        $this->assertContains('client.feature:pharma,award-prices', $route->gatherMiddleware());

        $model = file_get_contents(base_path('Modules/Pharma/Models/AwardWishlist.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_11_07_000001_create_pharma_award_wishlists_table.php'));
        $service = file_get_contents(base_path('Modules/Pharma/Services/PublicAwardWishlistService.php'));
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Pharma/Http/Controllers/PharmaAwardPriceController.php'));
        $wishlistView = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/pharma/award-wishlist.blade.php'));
        $this->assertStringContainsString('client.pharma.award-prices.wishlist.toggle', $wishlistView);
        $this->assertStringContainsString('Bỏ quan tâm', $wishlistView);
        $this->assertStringContainsString("where('user_id', (int) \$request->user('web')->id)", $controller);
        $this->assertStringContainsString("protected \$table = 'pharma_award_wishlists'", $model);
        $this->assertStringContainsString("['user_id', 'source_id']", $migration);
        $this->assertStringContainsString('lockForUpdate()', $service);
        $this->assertStringContainsString("where('user_id', \$userId)", $service);
        $this->assertStringContainsString("->where('source_id', \$sourceId)", $service);
        $this->assertStringContainsString("public function toggleWishlist(", $controller);
        $this->assertStringContainsString("if (! is_array(\$item))", $controller);
        foreach ([$model, $migration, $service, $controller] as $source) {
            $this->assertStringNotContainsString('Modules\\Muasamcong', $source);
        }
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
