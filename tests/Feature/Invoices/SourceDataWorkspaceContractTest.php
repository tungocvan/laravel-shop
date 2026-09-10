<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class SourceDataWorkspaceContractTest extends TestCase
{
    #[Test]
    public function source_data_workspace_uses_canonical_admin_controls_and_vietnamese_labels(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('<x-select-search id="source-data-partner-search" wire:model="partner" options-wire="partnerList"', $view);
        $this->assertStringContainsString('<x-search wire:model.live.debounce.300ms="search"', $view);
        $this->assertStringContainsString('wire:model.live="year"', $view);
        $this->assertStringContainsString('wire:model.live="month"', $view);
        $this->assertStringContainsString('wire:click="resetFilters"', $view);
        $this->assertStringContainsString('Trạng thái chi tiết', $view);
        $this->assertStringContainsString('Chi tiết sẵn sàng', $view);
        $this->assertStringContainsString("'GOODS' => 'Hàng hóa'", $view);
        $this->assertStringContainsString("'SERVICE_EXPENSE' => 'Dịch vụ / Chi phí'", $view);
        $this->assertStringContainsString("links('Invoices::vendor.pagination.admin-source-data')", $view);
        $this->assertStringContainsString('Thêm ghi chú', $view);
        $this->assertStringContainsString('<details class="group w-full">', $view);
        $this->assertStringNotContainsString('RAW detail', $view);
        $this->assertStringNotContainsString('HEADER READY', $view);
    }

    #[Test]
    public function source_data_filters_use_invoice_issued_date_and_reset_pagination(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));

        $this->assertStringContainsString("whereYear('issued_date'", $component);
        $this->assertStringContainsString("whereMonth('issued_date'", $component);
        $this->assertStringContainsString("\$query->where('name', \$partner);", $component);
        $this->assertStringContainsString('public array $partnerList = [];', $component);
        $this->assertStringContainsString('$this->partnerList = Invoices::query()', $component);
        $this->assertStringContainsString('public function updatedPartner(): void', $component);
        $this->assertStringContainsString('public function updatedYear(): void', $component);
        $this->assertStringContainsString('public function updatedMonth(): void', $component);
        $this->assertStringContainsString('public function updatedPerPage(): void', $component);
        $this->assertGreaterThanOrEqual(8, substr_count($component, '$this->resetPage();'));
        $this->assertStringContainsString("in_array(\$this->perPage, [25, 50, 100], true)", $component);
    }

    #[Test]
    public function source_data_sort_defaults_to_supplier_and_supports_four_admin_modes(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString("public string \$sortBy = 'supplier_asc';", $component);
        $this->assertStringContainsString('public function updatedSortBy(): void', $component);
        $this->assertStringContainsString("['supplier_asc', 'supplier_desc', 'date_desc', 'date_asc']", $component);
        $this->assertStringContainsString("\$this->sortBy = 'supplier_asc';", $component);
        $this->assertStringContainsString('$this->applySort($recordsQuery);', $component);
        $this->assertStringContainsString("select('name')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')", $component);
        $this->assertStringContainsString("select('tax_code')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')", $component);
        $this->assertStringContainsString("select('issued_date')->whereColumn('invoices.id', 'invoice_source_records.invoice_id')", $component);
        $this->assertStringContainsString('wire:model.live="sortBy"', $view);
        $this->assertStringContainsString('Nhà cung cấp A → Z', $view);
        $this->assertStringContainsString('Nhà cung cấp Z → A', $view);
        $this->assertStringContainsString('Ngày hóa đơn mới nhất', $view);
        $this->assertStringContainsString('Ngày hóa đơn cũ nhất', $view);
    }

    #[Test]
    public function source_data_classification_filter_clears_stale_row_drafts_on_first_change(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));

        $this->assertStringContainsString('public function updatedBusinessClassification(): void', $component);
        $this->assertStringContainsString('$this->businessClassifications = [];', $component);
        $this->assertStringContainsString('$this->businessNotes = [];', $component);
        $this->assertStringContainsString('$this->expenseCategoryIds = [];', $component);
        $this->assertStringContainsString('$this->expenseNotes = [];', $component);
        $this->assertStringContainsString("->when(\$this->businessClassification !== 'all', fn (\$query) => \$query->where('business_classification', \$this->businessClassification))", $component);
    }

    #[Test]
    public function source_data_dashboard_stats_follow_the_period_partner_and_invoice_type_scope(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));

        $this->assertStringContainsString('$scopeQuery = InvoiceSourceRecord::query()', $component);
        $this->assertStringContainsString("'total' => (clone \$scopeQuery)->count()", $component);
        $this->assertStringContainsString("'detail_ready' => (clone \$scopeQuery)->where('detail_status', 'READY')->count()", $component);
        $this->assertStringContainsString("'detail_missing' => (clone \$scopeQuery)->whereIn('detail_status', ['MISSING', 'ERROR'])->count()", $component);
        $this->assertStringContainsString("'unclassified' => (clone \$scopeQuery)->where('business_classification', 'UNCLASSIFIED')->count()", $component);
        $this->assertStringContainsString("'statsScopeLabel' => \$this->statsScopeLabel()", $component);
        $this->assertStringContainsString('Tháng ', $component);
        $this->assertStringContainsString('Tất cả kỳ dữ liệu', $component);
    }

    #[Test]
    public function source_data_save_action_exposes_a_readable_confirmation_modal(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('public bool $saveModalOpen = false;', $component);
        $this->assertStringContainsString('public array $saveModal = [];', $component);
        $this->assertStringContainsString('public function closeSaveModal(): void', $component);
        $this->assertStringContainsString('$this->showSaveModal($source, $classification, $note, true, $updated, $expenseCategoryId);', $component);
        $this->assertStringContainsString('$this->showSaveModal($source, $classification, $note, false, 1, $expenseCategoryId);', $component);
        $this->assertStringContainsString("'scope' => \$supplierWide ? 'Toàn bộ nhà cung cấp cùng MST và cùng loại hóa đơn' : 'Chỉ hóa đơn này'", $component);
        $this->assertStringContainsString('@if ($saveModalOpen)', $view);
        $this->assertStringContainsString('Nội dung phân loại vừa lưu', $view);
        $this->assertStringContainsString('Phạm vi áp dụng', $view);
        $this->assertStringContainsString('Ảnh hưởng:', $view);
        $this->assertStringContainsString('wire:click="closeSaveModal"', $view);
        $this->assertStringContainsString('Đóng và tiếp tục', $view);
    }

    #[Test]
    public function source_data_can_review_normalized_gdt_detail_before_classification(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('public bool $detailModalOpen = false;', $component);
        $this->assertStringContainsString('public function openDetailModal(int $sourceId): void', $component);
        $this->assertStringContainsString("\$source->detail_payload['hdhhdvu'] ?? []", $component);
        $this->assertStringContainsString('private function normalizeDetailItem(array $item, int $index): array', $component);
        $this->assertStringContainsString("['ten', 'ten_hhdv', 'thhdvu', 'name', 'description']", $component);
        $this->assertStringContainsString('wire:click="openDetailModal({{ $record->id }})"', $view);
        $this->assertStringContainsString('Chi tiết hóa đơn nguồn GDT', $view);
        $this->assertStringContainsString('Tên hàng hóa / dịch vụ', $view);
        $this->assertStringContainsString('Đóng và phân loại', $view);
    }

    #[Test]
    public function source_data_supports_one_click_supplier_batch_save_for_unclassified_review(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('public function saveSupplierBatch(): void', $component);
        $this->assertStringContainsString("\$classification === 'UNCLASSIFIED'", $component);
        $this->assertStringContainsString("\$supplierKey = \$taxCode.'|'.\$invoiceType;", $component);
        $this->assertStringContainsString('$this->applySupplierRule($source, $attributes);', $component);
        $this->assertStringContainsString("'mode' => 'batch'", $component);
        $this->assertStringContainsString('wire:model.live="applySameTaxCode.{{ $record->id }}"', $view);
        $this->assertStringContainsString('wire:click="saveSupplierBatch"', $view);
        $this->assertStringContainsString('Lưu nhanh nhiều nhà cung cấp', $view);
        $this->assertStringContainsString('Kết quả lưu hàng loạt nhà cung cấp', $view);
    }

    #[Test]
    public function source_data_supports_dynamic_expense_classification_level_two(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $model = file_get_contents(base_path('Modules/Invoices/Models/InvoiceSourceRecord.php'));
        $categoryModel = file_get_contents(base_path('Modules/Invoices/Models/InvoiceExpenseCategory.php'));
        $migration = file_get_contents(base_path('Modules/Invoices/database/migrations/2026_09_10_120000_add_expense_classification_to_invoice_source_records_table.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager.blade.php'));

        $this->assertStringContainsString('invoice_expense_categories', $migration);
        $this->assertStringContainsString("\$table->foreignId('parent_id')->nullable()", $migration);
        $this->assertStringContainsString("\$table->string('code', 64)->unique()", $migration);
        $this->assertStringContainsString("\$table->boolean('is_active')->default(true)", $migration);
        $this->assertStringContainsString("\$table->foreignId('expense_category_id')->nullable()", $migration);
        $this->assertStringContainsString('final class InvoiceExpenseCategory extends Model', $categoryModel);
        $this->assertStringContainsString('public function expenseCategory(): BelongsTo', $model);
        $this->assertStringContainsString("'expense_category_id'", $model);
        $this->assertStringContainsString('public array $expenseCategoryIds = [];', $component);
        $this->assertStringContainsString('InvoiceExpenseCategory::query()', $component);
        $this->assertStringContainsString('private function validatedExpenseCategoryId(', $component);
        $this->assertStringContainsString("\$classification === 'SERVICE_EXPENSE'", $component);
        $this->assertStringContainsString('Phân loại chi phí cấp 2', $view);
        $this->assertStringContainsString('wire:model="expenseCategoryIds.{{ $record->id }}"', $view);
        $this->assertStringContainsString('Chưa phân loại chi phí', $view);
        $this->assertStringContainsString('source-data-desktop-expense-{{ $record->id }}', $view);
        $this->assertStringContainsString('source-data-mobile-expense-{{ $record->id }}', $view);
    }

    #[Test]
    public function supplier_batch_checkbox_state_is_explicit_for_each_visible_source_row(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));

        $this->assertStringContainsString('public array $supplierBatchIds = [];', $component);
        $this->assertStringContainsString('public function updatedApplySameTaxCode(mixed $value, string|int $sourceId): void', $component);
        $this->assertStringContainsString('$this->rebuildApplySameTaxCodeState(array_keys($this->applySameTaxCode));', $component);
        $this->assertStringContainsString('$this->rebuildApplySameTaxCodeState($records->pluck(\'id\')->all());', $component);
        $this->assertStringContainsString('private function rebuildApplySameTaxCodeState(array $visibleIds): void', $component);
        $this->assertStringContainsString('$state[$visibleId] = isset($selected[$visibleId]);', $component);
        $this->assertStringContainsString('$this->applySameTaxCode = array_fill_keys(array_keys($this->applySameTaxCode), false);', $component);
        $this->assertStringContainsString('$selectedIds = collect($this->supplierBatchIds)', $component);
        $this->assertStringContainsString('$applySupplierWide = in_array($sourceId, array_map(\'intval\', $this->supplierBatchIds), true);', $component);
        $this->assertStringNotContainsString('$this->applySameTaxCode[$record->id] ??= $record->classification_scope === \'SUPPLIER\';', $component);
    }

    #[Test]
    public function source_data_keeps_backend_classification_and_supplier_scope_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $model = file_get_contents(base_path('Modules/Invoices/Models/InvoiceSourceRecord.php'));
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));

        $this->assertStringContainsString("'classification_scope' => \$supplierWide ? 'SUPPLIER' : 'INVOICE'", $component);
        $this->assertStringContainsString('private function applySupplierRule(InvoiceSourceRecord $source, array $attributes): int', $component);
        $this->assertStringContainsString('InvoiceSourceRecord::CLASSIFICATIONS', $component);
        $this->assertStringContainsString("'UNCLASSIFIED'", $model);
        $this->assertStringContainsString("'GOODS'", $model);
        $this->assertStringContainsString("'SERVICE_EXPENSE'", $model);
        $this->assertStringContainsString("'MIXED'", $model);
        $this->assertStringContainsString("Route::get('/source-data', [InvoicesController::class, 'sourceData'])->middleware('permission:invoices-create')->name('source-data');", $routes);
        $this->assertStringNotContainsString('GdtInvoiceService', $component);
    }
}
