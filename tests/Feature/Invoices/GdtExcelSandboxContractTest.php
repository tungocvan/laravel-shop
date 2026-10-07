<?php

namespace Tests\Feature\Invoices;

use Tests\TestCase;

class GdtExcelSandboxContractTest extends TestCase
{
    public function test_sandbox_has_isolated_route_ui_storage_and_does_not_reuse_canonical_import_flow(): void
    {
        $routes = file_get_contents(base_path('Modules/Invoices/routes/web.php'));
        $controller = file_get_contents(base_path('Modules/Invoices/Http/Controllers/InvoicesController.php'));
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/GdtExcelSandbox.php'));
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtExcelSandboxService.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/gdt-excel-sandbox.blade.php'));

        $this->assertStringContainsString("'/gdt-excel-test'", $routes);
        $this->assertStringContainsString("permission:invoices-configure", $routes);
        $this->assertStringContainsString("Invoices::pages.invoices.gdt-excel-test", $controller);
        $this->assertStringContainsString("'password' => ['required'", $component);
        $this->assertStringContainsString("in:sold,purchase", $component);
        $this->assertStringContainsString("storage_path('app/gdt-test/'", $service);
        $this->assertStringContainsString("new FastExcel", $service);
        $this->assertStringContainsString("/query/invoices/", $service);
        $this->assertStringContainsString('$cursor = $from->copy();', $service);
        $this->assertStringContainsString('$monthEnd = $cursor->copy()->endOfMonth();', $service);
        $this->assertStringContainsString('$this->fetchMonth(', $service);
        $this->assertStringContainsString("'state' =>", str_replace("if ($state) $query['state'] = $state;", "if ($state) 'state' => $state;", $service));
        $this->assertStringNotContainsString('InvoiceImportService', $component);
        $this->assertStringNotContainsString('Invoices::query()', $service);
        $this->assertStringContainsString('Dữ liệu test không import vào danh sách hóa đơn nghiệp vụ', file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/gdt-excel-test.blade.php')));
        $this->assertStringContainsString('Đổi công ty / đăng nhập lại', $view);
        $this->assertStringContainsString('Đồng bộ Excel', $view);
        $this->assertStringContainsString('File test đã lưu trên server', $view);
    }
}
