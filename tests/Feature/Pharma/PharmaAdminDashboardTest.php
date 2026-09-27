<?php

namespace Tests\Feature\Pharma;

use Modules\Pharma\Services\PharmaDashboardService;
use Tests\TestCase;

class PharmaAdminDashboardTest extends TestCase
{
    public function test_dashboard_route_is_registered_with_view_capability(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString("Route::get('/', PharmaDashboardController::class)", $routes);
        $this->assertStringContainsString("->middleware('can:view_pharma')", $routes);
        $this->assertStringContainsString("->name('dashboard')", $routes);
    }

    public function test_dashboard_uses_admin_layout_and_links_all_pharma_workspaces(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString("@extends('Admin::layouts.master')", $view);
        $this->assertStringContainsString("'admin.pharma.hssp.index'", $view);
        $this->assertStringContainsString("route('admin.pharma.drug-bid-awards.index')", $view);
        $this->assertStringContainsString("'admin.pharma.supplier-trackings.index'", $view);
        $this->assertStringContainsString("route('admin.pharma.price-lists.create')", $view);
    }

    public function test_dashboard_service_exposes_database_backed_metrics_and_price_list_summary(): void
    {
        $service = file_get_contents(base_path('Modules/Pharma/Services/PharmaDashboardService.php'));

        $this->assertStringContainsString('Medicine::query()->count()', $service);
        $this->assertStringContainsString('DrugBidAward::query()', $service);
        $this->assertStringContainsString('SupplierTracking::query()->count()', $service);
        $this->assertStringContainsString('PriceList::query()', $service);
        $this->assertStringContainsString("'price_lists' => \$this->priceListSummary()", $service);
    }

    public function test_quick_actions_are_permission_aware(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/pages/dashboard.blade.php'));

        $this->assertStringContainsString("@if(\$cap['create'])", $view);
        $this->assertStringContainsString("route('admin.pharma.price-lists.create')", $view);
        $this->assertStringContainsString("\$cap['edit']", $view);
        $this->assertStringContainsString("\$cap['official_facilities']", $view);
    }
}
