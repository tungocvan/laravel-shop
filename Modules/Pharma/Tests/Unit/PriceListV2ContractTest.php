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
        $this->assertTrue((new ReflectionClass(ResolvedPrice::class))->isReadOnly());
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
        foreach (["->name('index')", "->name('create')", "->name('store')", "->name('show')", "->name('edit')", "->name('update')", "->name('activate')", "->name('deactivate')", "->name('clone')", "->name('export')"] as $routeName) {
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
        foreach (['Medicine Master', 'Giá bán công ty', 'Giá thu thực tế', 'Giá xuất HĐ'] as $text) {
            $this->assertStringContainsString($text, $view);
        }
    }

    #[Test]
    public function commercial_builder_defaults_company_price_and_discounts_receivable_price(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        $this->assertStringContainsString("'company' => \$ceiling ?? ''", $livewire);
        $this->assertStringContainsString("['receivable'] = round((float) \$declared", $livewire);
        $this->assertStringNotContainsString("['company'] = round((float) \$declared", $livewire);
        $this->assertStringContainsString('Giá bán công ty', $view);
        $this->assertStringContainsString('Giảm từ giá kê khai', $view);
        $this->assertStringContainsString('Giá thu thực tế', $view);
    }

    #[Test]
    public function customer_builder_uses_partner_master_and_active_global_seed(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        $this->assertStringContainsString("whereJsonContains('partner_types', 'customer')", $livewire);
        $this->assertStringContainsString("where('status', 'active')", $livewire);
        $this->assertStringContainsString('loadFromGlobalPriceList', $livewire);
        $this->assertStringContainsString("where('type', PriceList::TYPE_GLOBAL)", $livewire);
        $this->assertStringContainsString("where('status', PriceList::STATUS_ACTIVE)", $livewire);
        $this->assertStringContainsString("route('admin.partners.index')", $view);
        $this->assertStringContainsString('Chưa có bảng giá chung ACTIVE đang hiệu lực', $view);
        $this->assertStringContainsString('Khởi tạo', $view);
    }

    #[Test]
    public function builder_defaults_dates_formats_money_and_can_exclude_seeded_skus(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));

        $this->assertStringContainsString('$this->effectiveFrom = now()->toDateString()', $livewire);
        $this->assertStringContainsString('$this->effectiveTo = now()->addMonth()->toDateString()', $livewire);
        $this->assertStringContainsString('public array $includedRows = []', $livewire);
        $this->assertStringContainsString('$this->includedRows = $this->selectedRows', $livewire);
        $this->assertStringContainsString('foreach ($this->includedRows as $key)', $livewire);
        $this->assertStringContainsString('updatePrice', $livewire);
        $this->assertStringContainsString('wire:model.live="includedRows"', $view);
        $this->assertStringContainsString('wire:model.live="includeAll"', $view);
        $this->assertStringContainsString("number_format((float)\$company,0,',','.')", $view);
        $this->assertStringContainsString('Bộ lọc danh mục', $view);
        $this->assertStringContainsString('Đặt lại bộ lọc', $view);
    }

    #[Test]
    public function create_workspace_is_four_steps_and_save_uses_success_modal(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        foreach (['Thông tin', 'Chọn thuốc', 'Thiết lập giá', 'Kiểm tra & lưu'] as $step) {
            $this->assertStringContainsString($step, $view);
        }
        $this->assertStringContainsString('selectAllMatching', $livewire);
        $this->assertStringContainsString('Chọn tất cả kết quả', $view);
        $this->assertStringContainsString('$this->savedModal = true', $livewire);
        $this->assertStringContainsString("redirectRoute('admin.pharma.price-lists.index'", $livewire);
        $this->assertStringContainsString('Đã lưu bảng giá Draft', $view);
        $this->assertStringContainsString('Về danh sách bảng giá', $view);
    }

    #[Test]
    public function persistence_contract_contains_snapshot_and_duplicate_protection(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_14_140000_create_price_lists_v2_tables.php'));
        foreach (["Schema::create('pharma_price_lists'", "Schema::create('pharma_price_list_items'", 'declared_price_snapshot', 'company_sale_price', 'actual_receivable_price', 'invoice_price', 'pharma_price_list_items_identity_unique'] as $text) {
            $this->assertStringContainsString($text, $migration);
        }
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
    public function admin_workspace_has_kpis_filters_pagination_modal_and_guarded_delete(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Index.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/PriceListManager.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/index.blade.php'));
        foreach (['Tổng bảng giá', 'Đang hiệu lực', 'Bảng giá chung', 'Theo khách hàng', 'Sắp hết hiệu lực'] as $text) {
            $this->assertStringContainsString($text, $view);
        }
        $this->assertStringContainsString('wire:model.live="perPage"', $view);
        $this->assertStringContainsString('confirmingId', $view);
        $this->assertStringContainsString("'delete'", $component);
        $this->assertStringContainsString('deleteDraft', $manager);
        $this->assertStringContainsString('Chỉ bảng giá DRAFT mới được xóa', $manager);
        $this->assertStringContainsString('Xóa Draft', $view);
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
