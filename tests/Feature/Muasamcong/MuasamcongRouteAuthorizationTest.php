<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MuasamcongRouteAuthorizationTest extends TestCase
{
    public function test_search_routes_use_admin_prefix_and_view_permission(): void
    {
        $index = Route::getRoutes()->getByName('muasamcong.index');
        $pricingExport = Route::getRoutes()->getByName('muasamcong.pricing.export-selected');
        $pricingHistoryDestroy = Route::getRoutes()->getByName('muasamcong.pricing.history.destroy');
        $pricingHistoryClear = Route::getRoutes()->getByName('muasamcong.pricing.history.clear');
        $hsmt = Route::getRoutes()->getByName('muasamcong.hsmt');
        $contractors = Route::getRoutes()->getByName('muasamcong.contractors');
        $contractorHistory = Route::getRoutes()->getByName('muasamcong.contractors.history');
        $contractorHistoryShow = Route::getRoutes()->getByName('muasamcong.contractors.history.show');
        $manualLotsShow = Route::getRoutes()->getByName('muasamcong.contractors.manual-lots.show');
        $manualLotsDownload = Route::getRoutes()->getByName('muasamcong.contractors.manual-lots.download');
        $synced = Route::getRoutes()->getByName('muasamcong.synced');
        $wishlist = Route::getRoutes()->getByName('muasamcong.wishlist');

        $this->assertNotNull($index);
        $this->assertNotNull($pricingExport);
        $this->assertNotNull($pricingHistoryDestroy);
        $this->assertNotNull($pricingHistoryClear);
        $this->assertNotNull($hsmt);
        $this->assertNotNull($contractors);
        $this->assertNotNull($contractorHistory);
        $this->assertNotNull($contractorHistoryShow);
        $this->assertNotNull($manualLotsShow);
        $this->assertNotNull($manualLotsDownload);
        $this->assertNotNull($synced);
        $this->assertNotNull($wishlist);
        $this->assertSame('admin/muasamcong', $index->uri());
        $this->assertSame('admin/muasamcong/pricing/export-selected', $pricingExport->uri());
        $this->assertSame(['POST'], $pricingExport->methods());
        $this->assertSame('admin/muasamcong/pricing/history/item', $pricingHistoryDestroy->uri());
        $this->assertContains('DELETE', $pricingHistoryDestroy->methods());
        $this->assertSame('admin/muasamcong/pricing/history', $pricingHistoryClear->uri());
        $this->assertContains('DELETE', $pricingHistoryClear->methods());
        $this->assertSame('admin/muasamcong/hsmt', $hsmt->uri());
        $this->assertSame('admin/muasamcong/contractors', $contractors->uri());
        $this->assertSame('admin/muasamcong/contractors/history', $contractorHistory->uri());
        $this->assertSame('admin/muasamcong/contractors/history/{contractorSearch}', $contractorHistoryShow->uri());
        $this->assertSame('admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots', $manualLotsShow->uri());
        $this->assertSame('admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download', $manualLotsDownload->uri());
        $this->assertSame('admin/muasamcong/synced', $synced->uri());
        $this->assertSame('admin/muasamcong/wishlist', $wishlist->uri());

        foreach ([$index, $pricingExport, $pricingHistoryDestroy, $pricingHistoryClear, $hsmt, $contractors, $contractorHistory, $contractorHistoryShow, $manualLotsShow, $manualLotsDownload, $synced, $wishlist] as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:admin', $middleware);
            $this->assertContains('permission:view_muasamcong,admin', $middleware);
            $this->assertNotContains('permission:muasamcong.config.manage,admin', $middleware);
        }
    }

    public function test_config_route_uses_dedicated_management_permission(): void
    {
        $config = Route::getRoutes()->getByName('muasamcong.config');
        $tool = Route::getRoutes()->getByName('muasamcong.session-tool.windows');

        $this->assertNotNull($config);
        $this->assertNotNull($tool);
        $this->assertSame('admin/muasamcong/config', $config->uri());
        $this->assertSame('admin/muasamcong/session-tool/windows', $tool->uri());

        foreach ([$config, $tool] as $route) {
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:admin', $middleware);
            $this->assertContains('permission:muasamcong.config.manage,admin', $middleware);
            $this->assertNotContains('permission:view_muasamcong,admin', $middleware);
        }
    }

    public function test_api_routes_remain_unchanged_and_module_routes_are_not_duplicated(): void
    {
        $uris = collect(Route::getRoutes()->getRoutes())
            ->map(fn ($route): string => $route->uri())
            ->filter(fn (string $uri): bool => str_contains($uri, 'muasamcong'))
            ->values();

        $this->assertCount(17, $uris);
        $this->assertContains('api/muasamcong', $uris);
        $this->assertContains('api/muasamcong/search-pricing', $uris);
        $this->assertContains('api/muasamcong/update-cookie', $uris);
        $this->assertContains('admin/muasamcong', $uris);
        $this->assertContains('admin/muasamcong/pricing/export-selected', $uris);
        $this->assertContains('admin/muasamcong/pricing/history/item', $uris);
        $this->assertContains('admin/muasamcong/pricing/history', $uris);
        $this->assertContains('admin/muasamcong/hsmt', $uris);
        $this->assertContains('admin/muasamcong/contractors', $uris);
        $this->assertContains('admin/muasamcong/contractors/history', $uris);
        $this->assertContains('admin/muasamcong/contractors/history/{contractorSearch}', $uris);
        $this->assertContains('admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots', $uris);
        $this->assertContains('admin/muasamcong/contractors/{contractorCode}/kqlcnt/{notifyNo}/manual-lots/download', $uris);
        $this->assertContains('admin/muasamcong/synced', $uris);
        $this->assertContains('admin/muasamcong/wishlist', $uris);
        $this->assertContains('admin/muasamcong/config', $uris);
        $this->assertContains('admin/muasamcong/session-tool/windows', $uris);
        $this->assertNotContains('muasamcong', $uris);
        $this->assertNotContains('muasamcong/hsmt', $uris);
        $this->assertNotContains('muasamcong/contractors', $uris);
        $this->assertNotContains('muasamcong/synced', $uris);
        $this->assertNotContains('muasamcong/wishlist', $uris);
        $this->assertNotContains('muasamcong/config', $uris);
    }
}
