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

        $this->assertStringContainsString('<x-select-search id="source-data-partner-search" wire:model="partner"', $view);
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
        $this->assertStringContainsString("$query->where('name', $partner);", $component);
        $this->assertStringContainsString('public function updatedPartner(): void', $component);
        $this->assertStringContainsString('public function updatedYear(): void', $component);
        $this->assertStringContainsString('public function updatedMonth(): void', $component);
        $this->assertStringContainsString('public function updatedPerPage(): void', $component);
        $this->assertGreaterThanOrEqual(8, substr_count($component, '$this->resetPage();'));
        $this->assertStringContainsString("in_array(\$this->perPage, [25, 50, 100], true)", $component);
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
    public function source_data_keeps_backend_classification_and_supplier_scope_contract(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));
        $model = file_get_contents(base_path('Modules/Invoices/Models/InvoiceSourceRecord.php'));
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));

        $this->assertStringContainsString("'classification_scope' => \$applySupplierWide ? 'SUPPLIER' : 'INVOICE'", $component);
        $this->assertStringContainsString('InvoiceSourceRecord::CLASSIFICATIONS', $component);
        $this->assertStringContainsString("'UNCLASSIFIED'", $model);
        $this->assertStringContainsString("'GOODS'", $model);
        $this->assertStringContainsString("'SERVICE_EXPENSE'", $model);
        $this->assertStringContainsString("'MIXED'", $model);
        $this->assertStringContainsString("Route::get('/source-data', [InvoicesController::class, 'sourceData'])->middleware('permission:invoices-create')->name('source-data');", $routes);
        $this->assertStringNotContainsString('GdtInvoiceService', $component);
    }
}
