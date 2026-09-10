<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceSourceDetailExportContractTest extends TestCase
{
    #[Test]
    public function source_data_export_supports_invoice_column_selection_or_current_filters(): void
    {
        $controller = file_get_contents(base_path('Modules/Invoices/Http/Controllers/InvoiceSourceDetailExportController.php'));
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));

        $this->assertStringContainsString("Route::post('/source-data/export-detail'", $routes);
        $this->assertStringContainsString('data-source-export-checkbox', $shell);
        $this->assertStringContainsString('data-source-export-select-all', $shell);
        $this->assertStringContainsString('source-data-desktop-row-', $shell);
        $this->assertStringContainsString('source-data-mobile-row-', $shell);
        $this->assertStringContainsString('Chọn xuất Excel', $shell);
        $this->assertStringContainsString('Chọn tất cả trang', $shell);
        $this->assertStringContainsString('Xuất Excel theo bộ lọc', $shell);
        $this->assertStringContainsString('button.textContent = count > 0 ? `Xuất ${count} hóa đơn đã chọn` : \'Xuất Excel theo bộ lọc\';', $shell);
        $this->assertStringContainsString("hidden.name = 'source_ids[]';", $shell);
        $this->assertStringContainsString('name="year"', $shell);
        $this->assertStringContainsString('name="month"', $shell);
        $this->assertStringContainsString('name="invoice_type"', $shell);
        $this->assertStringContainsString('name="partner"', $shell);
        $this->assertStringContainsString('name="search"', $shell);
        $this->assertStringContainsString('name="detail_status"', $shell);
        $this->assertStringContainsString('name="business_classification"', $shell);
        $this->assertStringNotContainsString('x-data="{', $shell);
        $this->assertStringNotContainsString('Chọn tất cả {{ number_format($records->count()) }} hóa đơn trên trang', $shell);

        $this->assertStringContainsString("\$selectedIds = collect((array) \$request->input('source_ids', []))", $controller);
        $this->assertStringContainsString('if ($selectedIds->isNotEmpty())', $controller);
        $this->assertStringContainsString("->whereIn('id', \$selectedIds)", $controller);
    }

    #[Test]
    public function source_data_export_shows_completion_modal_and_resets_successful_selection(): void
    {
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));

        $this->assertStringContainsString('data-source-export-modal', $shell);
        $this->assertStringContainsString('Đã xuất dữ liệu thành công', $shell);
        $this->assertStringContainsString('Các checkbox hóa đơn đã xuất đã được bỏ chọn', $shell);
        $this->assertStringContainsString('event.preventDefault();', $shell);
        $this->assertStringContainsString('const response = await fetch(form.action', $shell);
        $this->assertStringContainsString('const blob = await response.blob();', $shell);
        $this->assertStringContainsString('link.download = filename;', $shell);
        $this->assertStringContainsString('state.selected.clear();', $shell);
        $this->assertStringContainsString('if (root) syncCheckboxes(root);', $shell);
        $this->assertStringContainsString("showModal(root, 'Có lỗi khi tạo hoặc tải file Excel. Vui lòng thử lại.', true);", $shell);
    }

    #[Test]
    public function export_uses_exact_approved_twenty_columns_and_skips_zero_amount_detail_lines(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoiceSourceDetailExportService.php'));

        $expectedHeaders = [
            'Loại hóa đơn', 'Mã tra cứu', 'Ký hiệu', 'Số hóa đơn', 'Loại / tên hóa đơn', 'Ngày lập',
            'Mã số thuế đối tác', 'Đơn vị / đối tác', 'Địa chỉ', 'Email', 'Số điện thoại', 'Tiền VAT',
            'Tiền trước VAT', 'Tổng thanh toán', 'Chi tiết - ten', 'Chi tiết - dgia', 'Chi tiết - dvtinh',
            'Chi tiết - ltsuat', 'Chi tiết - sluong', 'Chi tiết - thtien',
        ];

        foreach ($expectedHeaders as $header) {
            $this->assertStringContainsString("'{$header}'", $service);
        }

        $this->assertCount(20, $expectedHeaders);
        $this->assertStringContainsString("\$source->detail_payload['hdhhdvu']", $service);
        $this->assertStringContainsString('->flatMap(', $service);
        $this->assertStringContainsString('->filter(fn (array $item) => $this->hasExportableAmount($item))', $service);
        $this->assertStringContainsString("\$amount = Arr::get(\$detail, 'thtien');", $service);
        $this->assertStringContainsString("return (float) \$amount != 0.0;", $service);
        $this->assertStringContainsString("'Số hóa đơn' => \$invoice?->invoice_number", $service);
        $this->assertStringContainsString("'Chi tiết - thtien' => \$this->excelValue(Arr::get(\$detail, 'thtien'))", $service);
        $this->assertStringNotContainsString('Nhà cung cấp dữ liệu', $service);
        $this->assertStringNotContainsString('Trạng thái detail', $service);
        $this->assertStringNotContainsString('Phân loại nghiệp vụ', $service);
    }
}
