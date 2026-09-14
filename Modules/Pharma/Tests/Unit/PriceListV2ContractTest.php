<?php

namespace Modules\Pharma\Tests\Unit;

use Modules\Pharma\Contracts\PriceResolver;
use Modules\Pharma\DTOs\ResolvedPrice;
use Modules\Pharma\Models\PriceListItem;
use PHPUnit\Framework\Attributes\Test;
use ReflectionClass;
use Tests\TestCase;

class PriceListV2ContractTest extends TestCase
{
    #[Test]
    public function price_resolver_exposes_stable_contract_and_readonly_dto(): void
    {
        $resolver = new ReflectionClass(PriceResolver::class);
        $this->assertTrue($resolver->isInterface());
        $this->assertTrue($resolver->hasMethod('resolve'));

        $dto = new ReflectionClass(ResolvedPrice::class);
        $this->assertTrue($dto->isReadOnly());
    }

    #[Test]
    public function sku_package_identity_is_deterministic_even_without_package(): void
    {
        $this->assertSame('variant:12:package:0', PriceListItem::makeIdentityKey(12, null));
        $this->assertSame('variant:12:package:34', PriceListItem::makeIdentityKey(12, 34));
    }

    #[Test]
    public function price_list_v2_routes_are_complete(): void
    {
        $routes = file_get_contents(base_path('Modules/Pharma/routes/web.php'));

        $this->assertStringContainsString("Route::prefix('price-lists')->name('price-lists.')", $routes);

        foreach ([
            "->name('index')",
            "->name('create')",
            "->name('store')",
            "->name('show')",
            "->name('edit')",
            "->name('update')",
            "->name('activate')",
            "->name('deactivate')",
            "->name('clone')",
            "->name('export')",
        ] as $routeName) {
            $this->assertStringContainsString($routeName, $routes);
        }
    }

    #[Test]
    public function create_flow_no_longer_uses_legacy_workbook_source(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));

        $this->assertStringNotContainsString('BANG_GIA_TONG_HOP.xlsx', $livewire);
        $this->assertStringNotContainsString('WorkbookAnalyzer', $livewire);
        $this->assertStringNotContainsString('PriceListService', $livewire);
        $this->assertStringNotContainsString('BANG_GIA_TONG_HOP.xlsx', $view);
        $this->assertStringContainsString('Medicine Master', $view);
        $this->assertStringContainsString('Giá bán công ty', $view);
        $this->assertStringContainsString('Giá thu thực tế', $view);
        $this->assertStringContainsString('Giá xuất HĐ', $view);
    }

    #[Test]
    public function persistence_contract_contains_snapshot_and_duplicate_protection(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_14_140000_create_price_lists_v2_tables.php'));

        $this->assertStringContainsString("Schema::create('pharma_price_lists'", $migration);
        $this->assertStringContainsString("Schema::create('pharma_price_list_items'", $migration);
        $this->assertStringContainsString('declared_price_snapshot', $migration);
        $this->assertStringContainsString('company_sale_price', $migration);
        $this->assertStringContainsString('actual_receivable_price', $migration);
        $this->assertStringContainsString('invoice_price', $migration);
        $this->assertStringContainsString('pharma_price_list_items_identity_unique', $migration);
    }

    #[Test]
    public function resolver_order_is_customer_then_global_and_never_declared_price_fallback(): void
    {
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/DatabasePriceResolver.php'));

        $customerPosition = strpos($resolver, 'PriceList::TYPE_CUSTOMER');
        $globalPosition = strpos($resolver, 'PriceList::TYPE_GLOBAL');
        $this->assertNotFalse($customerPosition);
        $this->assertNotFalse($globalPosition);
        $this->assertLessThan($globalPosition, $customerPosition);

        $this->assertStringContainsString('declared_price_snapshot', $resolver);
        $this->assertStringNotContainsString('Medicine::', $resolver);
        $this->assertSame(0, preg_match('/->declared_price(?!_)/', $resolver));
    }

    #[Test]
    public function admin_workspace_has_kpis_filters_pagination_and_modal_actions(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/index.blade.php'));

        foreach (['Tổng bảng giá', 'Đang hiệu lực', 'Bảng giá chung', 'Bảng giá khách hàng', 'Sắp hết hiệu lực'] as $text) {
            $this->assertStringContainsString($text, $view);
        }
        $this->assertStringContainsString('wire:model.live="perPage"', $view);
        $this->assertStringContainsString('confirmingId', $view);
        $this->assertStringContainsString('Xác nhận thao tác', $view);
    }

    #[Test]
    public function export_supports_selected_items_or_all_items(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $show = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));

        $this->assertStringContainsString("query('items', [])", $controller);
        $this->assertStringContainsString('if ($selected->isNotEmpty())', $controller);
        $this->assertStringContainsString('name="items[]"', $show);
        $this->assertStringContainsString('không chọn sẽ export toàn bộ', $show);
    }
}
