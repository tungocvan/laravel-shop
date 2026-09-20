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
        foreach (["->name('index')", "->name('create')", "->name('store')", "->name('show')", "->name('edit')", "->name('update')", "->name('activate')", "->name('deactivate')", "->name('clone')", "->name('export')"] as $routeName) $this->assertStringContainsString($routeName, $routes);
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
        foreach (['Medicine Master', 'Giá bán công ty', 'Giá thu thực tế', 'Giá xuất HĐ'] as $text) $this->assertStringContainsString($text, $view);
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
        $this->assertStringContainsString('loadFromGlobalPriceList', $livewire);
        $this->assertStringContainsString("where('type', PriceList::TYPE_GLOBAL)", $livewire);
        $this->assertStringContainsString("route('admin.partners.index')", $view);
        $this->assertStringContainsString('Khởi tạo', $view);
    }

    #[Test]
    public function customer_builder_tracks_manager_and_reusable_business_purpose(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/PriceListManager.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_15_160000_add_customer_ownership_and_purpose_to_price_lists.php'));

        foreach (['manager_user_id', 'purpose_id', 'pharma_price_list_purposes'] as $field) $this->assertStringContainsString($field, $migration);
        $this->assertStringContainsString('$this->managerUserId = auth(\'admin\')->id()', $livewire);
        $this->assertStringContainsString('createPurpose', $livewire);
        $this->assertStringContainsString("where('is_active', true)", $manager);
        foreach (['Người phụ trách khách hàng', 'Mục đích bảng giá', 'Thêm mục đích sử dụng', 'Giải nghĩa:', 'Thêm & chọn'] as $text) $this->assertStringContainsString($text, $view);
    }

    #[Test]
    public function customer_builder_supports_partner_or_official_facility_and_manages_purposes(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/workspace-bid.blade.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/PriceListManager.php'));
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceList.php'));

        foreach (['CUSTOMER_SOURCE_PARTNER', 'CUSTOMER_SOURCE_OFFICIAL_FACILITY', 'official_facility_id', 'customer_source'] as $text) {
            $this->assertStringContainsString($text, $model.$livewire.$manager);
        }
        $this->assertStringContainsString('placeholder="Tìm khách hàng..."', $view);
        $this->assertStringContainsString('placeholder="Tìm cơ sở KCB..."', $view);
        $this->assertStringContainsString('facility_name', $livewire);
        $this->assertStringContainsString('x-data="{ open:false', $view);
        $this->assertStringContainsString('editPurpose', $livewire);
        $this->assertStringContainsString('deletePurpose', $livewire);
        $this->assertStringContainsString('priceLists()->exists()', $livewire);
        $this->assertStringContainsString('Đổi tên', $view);
        $this->assertStringContainsString('wire:confirm=', $view);
        $indexView = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/index.blade.php'));
        $indexClass = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Index.php'));
        $this->assertStringContainsString("route('admin.pharma.price-lists.edit',\$list)", $indexView);
        $this->assertStringContainsString("status==='draft'", $indexView);
        $this->assertStringContainsString('officialFacility?->facility_name', $indexView);
        $this->assertStringContainsString("'officialFacility'", $indexClass);
        $model = file_get_contents(base_path('Modules/Pharma/Models/PriceList.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $this->assertStringContainsString('isDirectlyEditable', $model);
        $this->assertStringContainsString('STATUS_INACTIVE', $model);
        $this->assertStringContainsString('isDirectlyEditable()', $controller);
        $this->assertStringContainsString("in_array(\$list->status,['draft','inactive'],true)", $indexView);
        $this->assertGreaterThanOrEqual(2, substr_count($indexView, "confirm({{ \$list->id }},'activate')"));
        $this->assertStringContainsString('Kích hoạt', $indexView);
        $indexPage = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/index.blade.php'));
        $showPage = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $this->assertStringContainsString("@livewire('pharma.price-list.export-configurator')", $indexPage);
        $this->assertStringNotContainsString("@livewire('pharma.price-list.export-configurator')", $showPage);
        $this->assertStringContainsString('Mẫu bảng báo giá', $showPage);
        $this->assertStringContainsString('pharma-export-profile-id', $showPage);
        $this->assertStringContainsString('profilesForUser', $controller);
        $workspace = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/workspace-bid.blade.php'));
        $this->assertStringContainsString('wire:model.live="includeAll"', $workspace);
        $this->assertStringContainsString('Áp dụng tất cả', $workspace);
        $page = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/create.blade.php'));
        $this->assertStringContainsString("@livewire('pharma.price-list.workspace')", $page);
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
        $this->assertStringContainsString('number_format(', $view);
        $this->assertStringContainsString("','", $view);
        $this->assertStringContainsString("'.'", $view);
        $this->assertStringContainsString("₫", $view);
        $this->assertStringContainsString('Bộ lọc danh mục', $view);
        $this->assertStringContainsString('Đặt lại bộ lọc', $view);
    }

    #[Test]
    public function create_workspace_is_four_steps_and_save_uses_success_modal(): void
    {
        $livewire = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Create.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/create.blade.php'));
        foreach (['Thông tin', 'Chọn thuốc', 'Thiết lập giá', 'Kiểm tra & lưu'] as $step) $this->assertStringContainsString($step, $view);
        $this->assertStringContainsString('selectAllMatching', $livewire);
        $this->assertStringContainsString('Chọn tất cả kết quả', $view);
        $this->assertStringContainsString('$this->savedModal = true', $livewire);
        $this->assertStringContainsString("redirectRoute('admin.pharma.price-lists.index'", $livewire);
        $this->assertStringContainsString('Đã lưu bảng giá Draft', $view);
        $this->assertStringContainsString('Về danh sách bảng giá', $view);
    }

    #[Test]
    public function catalog_rows_have_stable_livewire_identity_across_pagination(): void
    {
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/workspace-bid.blade.php'));

        $this->assertStringContainsString('<tbody wire:replace wire:key="catalog-desktop-page-', $view);
        $this->assertStringContainsString('wire:key="catalog-desktop-row-{{ $row->key }}"', $view);
        $this->assertStringContainsString('wire:key="catalog-desktop-checkbox-{{ $row->key }}"', $view);
        $this->assertStringContainsString('wire:replace wire:key="catalog-mobile-page-', $view);
        $this->assertStringContainsString('wire:key="catalog-mobile-row-{{ $row->key }}"', $view);
        $this->assertStringContainsString('wire:key="catalog-mobile-checkbox-{{ $row->key }}"', $view);
        $this->assertStringContainsString('@checked(in_array($row->key, $selectedRows, true))', $view);
        $this->assertSame(2, substr_count($view, 'wire:model.live="selectedRows"'));
    }

    #[Test]
    public function persistence_contract_contains_snapshot_and_duplicate_protection(): void
    {
        $migration = file_get_contents(base_path('Modules/Pharma/database/migrations/2026_09_14_140000_create_price_lists_v2_tables.php'));
        foreach (["Schema::create('pharma_price_lists'", "Schema::create('pharma_price_list_items'", 'declared_price_snapshot', 'company_sale_price', 'actual_receivable_price', 'invoice_price', 'pharma_price_list_items_identity_unique'] as $text) $this->assertStringContainsString($text, $migration);
    }

    #[Test]
    public function resolver_order_is_customer_then_global_and_never_declared_price_fallback(): void
    {
        $resolver = file_get_contents(base_path('Modules/Pharma/Services/DatabasePriceResolver.php'));
        $customerPosition = strpos($resolver, 'PriceList::TYPE_CUSTOMER'); $globalPosition = strpos($resolver, 'PriceList::TYPE_GLOBAL');
        $this->assertNotFalse($customerPosition); $this->assertNotFalse($globalPosition); $this->assertLessThan($globalPosition, $customerPosition);
        $this->assertStringContainsString('declared_price_snapshot', $resolver); $this->assertStringNotContainsString('Medicine::', $resolver); $this->assertSame(0, preg_match('/->declared_price(?!_)/', $resolver));
    }

    #[Test]
    public function admin_workspace_has_kpis_filters_pagination_modal_and_guarded_delete(): void
    {
        $component = file_get_contents(base_path('Modules/Pharma/Livewire/PriceList/Index.php'));
        $manager = file_get_contents(base_path('Modules/Pharma/Services/PriceListManager.php'));
        $view = file_get_contents(base_path('Modules/Pharma/resources/views/livewire/price-list/index.blade.php'));
        foreach (['Tổng bảng giá', 'Đang hiệu lực', 'Bảng giá chung', 'Theo khách hàng', 'Sắp hết hiệu lực'] as $text) $this->assertStringContainsString($text, $view);
        $this->assertStringContainsString('wire:model.live="perPage"', $view); $this->assertStringContainsString('confirmingId', $view); $this->assertStringContainsString("'delete'", $component);
        $this->assertStringContainsString('wire:model.live="selectedIds"', $view);
        $this->assertStringContainsString('confirmBulkDelete', $component);
        $this->assertStringContainsString('deleteSelected', $manager);
        $this->assertStringContainsString('[PriceList::STATUS_DRAFT, PriceList::STATUS_INACTIVE]', $manager);
        $this->assertStringContainsString('ACTIVE phải được ngưng trước khi xóa', $manager);
        $this->assertStringContainsString('Xóa bảng giá đã chọn', $view);
        $this->assertStringContainsString('không xóa một phần', $view);
        foreach (['managerUserId', 'effectiveFrom', 'effectiveTo'] as $property) $this->assertStringContainsString($property, $component);
        $this->assertStringContainsString("'manager'", $component);
        $this->assertStringContainsString("where('manager_user_id'", $component);
        $this->assertStringContainsString("whereNull('effective_to')->orWhereDate('effective_to', '>=',", $component);
        $this->assertStringContainsString("whereNull('effective_from')->orWhereDate('effective_from', '<=',", $component);
        foreach (['wire:model.live="managerUserId"', 'wire:model.live="effectiveFrom"', 'wire:model.live="effectiveTo"', 'Người phụ trách', 'Hiệu lực từ', 'Hiệu lực đến', 'manager?->name'] as $text) $this->assertStringContainsString($text, $view);
    }

    #[Test]
    public function export_supports_selected_items_or_all_items(): void
    {
        $controller = file_get_contents(base_path('Modules/Pharma/Http/Controllers/PriceListController.php'));
        $show = file_get_contents(base_path('Modules/Pharma/resources/views/pages/price-list/show.blade.php'));
        $this->assertStringContainsString("'items'=>['nullable','array']", str_replace(' ', '', $controller));
        $this->assertStringContainsString("\$validated['items']??[]", str_replace(' ', '', $controller));
        $this->assertStringContainsString('if($selected->isNotEmpty())', str_replace(' ', '', $controller));
        $this->assertStringContainsString('name="items[]"', $show);
        $this->assertStringContainsString('Không chọn sản phẩm', $show);
        $this->assertStringContainsString('Không chọn sản phẩm → xuất toàn bộ', $show);
    }
}
