<?php

namespace Tests\Feature\Muasamcong;

use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class MuasamcongRouteAuthorizationTest extends TestCase
{
    public function test_search_routes_use_admin_prefix_and_view_permission(): void
    {
        $dashboard = Route::getRoutes()->getByName('muasamcong.dashboard');
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
        $syncedExport = Route::getRoutes()->getByName('muasamcong.synced.export-selected');
        $syncedBbg = Route::getRoutes()->getByName('muasamcong.synced.export-bbg');
        $wishlist = Route::getRoutes()->getByName('muasamcong.wishlist');
        $wishlistExport = Route::getRoutes()->getByName('muasamcong.wishlist.export-selected');
        $wishlistDestroy = Route::getRoutes()->getByName('muasamcong.wishlist.destroy-selected');

        foreach ([$dashboard, $index, $pricingExport, $pricingHistoryDestroy, $pricingHistoryClear, $hsmt, $contractors, $contractorHistory, $contractorHistoryShow, $manualLotsShow, $manualLotsDownload, $synced, $syncedExport, $syncedBbg, $wishlist, $wishlistExport, $wishlistDestroy] as $route) {
            $this->assertNotNull($route);
            $middleware = $route->gatherMiddleware();
            $this->assertContains('auth:admin', $middleware);
            $this->assertContains('permission:view_muasamcong,admin', $middleware);
            $this->assertNotContains('permission:muasamcong.config.manage,admin', $middleware);
        }

        $this->assertSame('admin/muasamcong/dashboard', $dashboard->uri());
        $this->assertSame(['GET', 'HEAD'], $dashboard->methods());
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
        $this->assertSame('admin/muasamcong/synced/export-selected', $syncedExport->uri());
        $this->assertSame(['POST'], $syncedExport->methods());
        $this->assertSame('admin/muasamcong/synced/export-bbg', $syncedBbg->uri());
        $this->assertSame(['POST'], $syncedBbg->methods());
        $this->assertSame('admin/muasamcong/wishlist', $wishlist->uri());
        $this->assertSame('admin/muasamcong/wishlist/export-selected', $wishlistExport->uri());
        $this->assertSame(['POST'], $wishlistExport->methods());
        $this->assertSame('admin/muasamcong/wishlist/selected', $wishlistDestroy->uri());
        $this->assertContains('DELETE', $wishlistDestroy->methods());
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
        $routes = collect(Route::getRoutes()->getRoutes());
        $moduleRoutes = $routes
            ->filter(function ($route): bool {
                $uri = $route->uri();

                return str_starts_with($uri, 'admin/muasamcong')
                    || str_starts_with($uri, 'api/muasamcong');
            })
            ->values();
        $uris = $moduleRoutes->map(fn ($route): string => $route->uri());
        $signatures = $moduleRoutes->flatMap(
            fn ($route) => collect($route->methods())
                ->map(fn (string $method): string => $method.' '.$route->uri())
        );

        $this->assertSame([], $signatures->duplicates()->values()->all());
        $this->assertContains('api/muasamcong', $uris);
        $this->assertContains('api/muasamcong/search-pricing', $uris);
        $this->assertContains('api/muasamcong/update-cookie', $uris);
        $this->assertContains('admin/muasamcong/dashboard', $uris);
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
        $this->assertContains('admin/muasamcong/synced/export-selected', $uris);
        $this->assertContains('admin/muasamcong/synced/export-bbg', $uris);
        $this->assertContains('admin/muasamcong/wishlist', $uris);
        $this->assertContains('admin/muasamcong/wishlist/export-selected', $uris);
        $this->assertContains('admin/muasamcong/wishlist/selected', $uris);
        $this->assertContains('admin/muasamcong/config', $uris);
        $this->assertContains('admin/muasamcong/session-tool/windows', $uris);

        $allUris = $routes->map(fn ($route): string => $route->uri());
        $this->assertNotContains('muasamcong', $allUris);
        $this->assertNotContains('muasamcong/hsmt', $allUris);
        $this->assertNotContains('muasamcong/contractors', $allUris);
        $this->assertNotContains('muasamcong/synced', $allUris);
        $this->assertNotContains('muasamcong/wishlist', $allUris);
        $this->assertNotContains('muasamcong/config', $allUris);
    }

}
