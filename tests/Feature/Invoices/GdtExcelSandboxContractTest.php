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
        $this->assertStringContainsString("if (\$state) \$query['state'] = \$state;", $service);
        $this->assertStringContainsString("storage_path('app/gdt-test/'.\$userId.'/'.\$taxCode)", $service);
        $this->assertStringNotContainsString('InvoiceImportService', $component);
        $this->assertStringNotContainsString('Invoices::query()', $service);
        $this->assertStringContainsString('Dữ liệu test không import vào danh sách hóa đơn nghiệp vụ', file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/gdt-excel-test.blade.php')));
        $this->assertStringContainsString('Đổi công ty / đăng nhập lại', $view);
        $this->assertStringContainsString('Đồng bộ Excel', $view);
        $this->assertStringContainsString('File test đã lưu trên server', $view);
        $this->assertStringContainsString('Crypt::encryptString($password)', $service);
        $this->assertStringContainsString('Crypt::decryptString($encrypted)', $service);
        $this->assertStringContainsString("storage_path('app/gdt-test/'.\$userId.'/accounts.json')", $service);
        $this->assertStringContainsString('existingExport(', $service);
        $this->assertStringContainsString("\$this->fetchMonth((string) \$session['token'], \$chunkStart, \$chunkEnd, \$type, '8')", $service);
        $this->assertStringContainsString("\$query['search'] .= ';ttxly=='", $service);
        $this->assertStringContainsString("'/sco-query/invoices/'", $service);
        $this->assertStringContainsString('collect($rows)->unique(', $service);
        $this->assertStringContainsString('deleteFile(', $service);
        $this->assertStringContainsString('chooseSavedAccount', $component);
        $this->assertStringContainsString('connectSaved', $component);
        $this->assertStringContainsString('fileTaxCodeFilter', $component);
        $this->assertStringContainsString('Khoảng thời gian này đã được đồng bộ', $view);
        $this->assertStringContainsString('Lọc mã số thuế', $view);
        $this->assertStringContainsString('wire:confirm="Xóa file Excel này khỏi server?"', $view);
    }
}
