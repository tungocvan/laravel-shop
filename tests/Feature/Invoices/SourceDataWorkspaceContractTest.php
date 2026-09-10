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

        $this->assertStringContainsString('<x-search wire:model.live.debounce.300ms="search"', $view);
        $this->assertStringContainsString('wire:model.live="year"', $view);
        $this->assertStringContainsString('wire:model.live="month"', $view);
        $this->assertStringContainsString('wire:click="resetFilters"', $view);
        $this->assertStringContainsString('Trạng thái chi tiết', $view);
        $this->assertStringContainsString('Chi tiết sẵn sàng', $view);
        $this->assertStringContainsString("'GOODS' => 'Hàng hóa'", $view);
        $this->assertStringContainsString("'SERVICE_EXPENSE' => 'Dịch vụ / Chi phí'", $view);
        $this->assertStringContainsString("links('Invoices::vendor.pagination.admin-source-data')", $view);
        $this->assertStringNotContainsString('RAW detail', $view);
        $this->assertStringNotContainsString('HEADER READY', $view);
    }

    #[Test]
    public function source_data_filters_use_invoice_issued_date_and_reset_pagination(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SourceDataManager.php'));

        $this->assertStringContainsString("whereYear('issued_date'", $component);
        $this->assertStringContainsString("whereMonth('issued_date'", $component);
        $this->assertStringContainsString('public function updatedYear(): void', $component);
        $this->assertStringContainsString('public function updatedMonth(): void', $component);
        $this->assertStringContainsString('public function updatedPerPage(): void', $component);
        $this->assertGreaterThanOrEqual(7, substr_count($component, '$this->resetPage();'));
        $this->assertStringContainsString("in_array(\$this->perPage, [25, 50, 100], true)", $component);
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
