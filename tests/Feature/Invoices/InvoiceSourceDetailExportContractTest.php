<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class InvoiceSourceDetailExportContractTest extends TestCase
{
    #[Test]
    public function source_data_export_supports_selected_invoices_or_current_filters(): void
    {
        $controller = file_get_contents(base_path('Modules/Invoices/Http/Controllers/InvoiceSourceDetailExportController.php'));
        $shell = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/source-data-manager-shell.blade.php'));
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));

        $this->assertStringContainsString("Route::post('/source-data/export-detail'", $routes);
        $this->assertStringContainsString("name=\"source_ids[]\"", $shell);
        $this->assertStringContainsString('Chọn tất cả', $shell);
        $this->assertStringContainsString('Xuất theo bộ lọc', $shell);
        $this->assertStringContainsString("'Xuất ' + selected.length + ' hóa đơn đã chọn'", $shell);
        $this->assertStringContainsString('name="year"', $shell);
        $this->assertStringContainsString('name="month"', $shell);
        $this->assertStringContainsString('name="invoice_type"', $shell);
        $this->assertStringContainsString('name="partner"', $shell);
        $this->assertStringContainsString('name="search"', $shell);
        $this->assertStringContainsString('name="detail_status"', $shell);
        $this->assertStringContainsString('name="business_classification"', $shell);

        $this->assertStringContainsString("$selectedIds = collect((array) $request->input('source_ids', []))", $controller);
        $this->assertStringContainsString('if ($selectedIds->isNotEmpty())', $controller);
        $this->assertStringContainsString("->whereIn('id', $selectedIds)", $controller);
        $this->assertStringContainsString("$request->input('invoice_type', 'purchase')", $controller);
        $this->assertStringContainsString("$request->input('detail_status', 'all')", $controller);
        $this->assertStringContainsString("$request->input('business_classification', 'all')", $controller);
    }

    #[Test]
    public function export_uses_exact_approved_twenty_columns_and_repeats_invoice_data_for_each_detail_line(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/InvoiceSourceDetailExportService.php'));

        $expectedHeaders = [
            'Loại hóa đơn',
            'Mã tra cứu',
            'Ký hiệu',
            'Số hóa đơn',
            'Loại / tên hóa đơn',
            'Ngày lập',
            'Mã số thuế đối tác',
            'Đơn vị / đối tác',
            'Địa chỉ',
            'Email',
            'Số điện thoại',
            'Tiền VAT',
            'Tiền trước VAT',
            'Tổng thanh toán',
            'Chi tiết - ten',
            'Chi tiết - dgia',
            'Chi tiết - dvtinh',
            'Chi tiết - ltsuat',
            'Chi tiết - sluong',
            'Chi tiết - thtien',
        ];

        foreach ($expectedHeaders as $header) {
            $this->assertStringContainsString("'{$header}'", $service);
        }

        $this->assertCount(20, $expectedHeaders);
        $this->assertStringContainsString("$source->detail_payload['hdhhdvu']", $service);
        $this->assertStringContainsString('->flatMap(', $service);
        $this->assertStringContainsString("'Số hóa đơn' => $invoice?->invoice_number", $service);
        $this->assertStringContainsString("'Ngày lập' => $invoice?->issued_date?->format('d/m/Y')", $service);
        $this->assertStringContainsString("'Mã số thuế đối tác' => $invoice?->tax_code", $service);
        $this->assertStringContainsString("'Tiền trước VAT' => $invoice?->amount_before_vat", $service);
        $this->assertStringContainsString("'Tổng thanh toán' => $invoice?->total_amount", $service);
        $this->assertStringNotContainsString('Nhà cung cấp dữ liệu', $service);
        $this->assertStringNotContainsString('Trạng thái detail', $service);
        $this->assertStringNotContainsString('Phân loại nghiệp vụ', $service);
        $this->assertStringNotContainsString('Thời điểm lấy detail', $service);
        $this->assertStringNotContainsString('Lỗi detail gần nhất', $service);
    }
}
