<?php

namespace Tests\Feature\Website;

use Tests\TestCase;
use Modules\Website\Services\PharmaQuickOrderDisplayService;
use Modules\Website\Models\PharmaQuickOrderRequest;

final class PharmaQuickOrderAccountAndReviewUiContractTest extends TestCase
{
    public function test_account_requests_are_scoped_to_current_user_and_regular_orders_remain_available(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/AccountController.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/account/orders/index.blade.php'));
        $routes = file_get_contents(base_path('Modules/Website/routes/web.php'));
        $this->assertStringContainsString('->where(\'user_id\', $request->user()->id)', $controller);
        $this->assertStringContainsString('->findOrFail($id)', $controller);
        $this->assertStringContainsString("Route::get('/pharma-requests/{id}'", $routes);
        $this->assertStringContainsString("request('tab', 'pharma')", $view);
        $this->assertStringContainsString("@livewire('website.account.order-list')", $view);
    }

    public function test_product_display_resolves_names_without_trusting_browser_prices(): void
    {
        $service = file_get_contents(base_path('Modules/Website/Services/PharmaQuickOrderDisplayService.php'));
        $detail = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/request-detail.blade.php'));
        $this->assertStringContainsString('Product::query()->whereIn(\'id\', $ids)', $service);
        $this->assertStringContainsString('\'title\' => $product?->title', $service);
        $this->assertStringContainsString("Cần kiểm tra", $detail);
        $this->assertStringContainsString('{{ $line[\'title\'] }}', $detail);
        $this->assertStringContainsString('{{ $line[\'quantity\'] }}', $detail);
    }

    public function test_admin_queue_has_counts_and_search(): void
    {
        $controller = file_get_contents(base_path('Modules/Website/Http/Controllers/Admin/PharmaQuickOrderReviewController.php'));
        $view = file_get_contents(base_path('Modules/Website/resources/views/pages/admin/pharma-quick-order/requests.blade.php'));
        $this->assertStringContainsString("groupBy('status')", $controller);
        $this->assertStringContainsString("orWhere('tax_code'", $controller);
        $this->assertStringContainsString('name="search"', $view);
        $this->assertStringContainsString('$counts[$key] ?? 0', $view);
    }
}
