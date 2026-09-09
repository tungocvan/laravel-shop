<?php

namespace Tests\Feature;

use Tests\TestCase;

class InvoicesGdtQueueTokenGuardTest extends TestCase
{
    public function test_queue_is_default_and_gdt_token_is_checked_before_dispatch(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/SearchHoadon.php'));

        $this->assertIsString($component);
        $this->assertStringContainsString('public $useQueue = true;', $component);
        $this->assertStringContainsString("if (! \$this->apiService->hasToken())", $component);
        $this->assertStringContainsString("redirectRoute('admin.invoices.create-token')", $component);

        $tokenGuard = strpos($component, "if (! \$this->apiService->hasToken())");
        $dispatch = strpos($component, 'ProcessGdtInvoicesJob::dispatch');

        $this->assertNotFalse($tokenGuard);
        $this->assertNotFalse($dispatch);
        $this->assertLessThan($dispatch, $tokenGuard);
    }

    public function test_sync_workspace_exposes_gdt_connection_action(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/sync.blade.php'));

        $this->assertIsString($view);
        $this->assertStringContainsString('GDT chưa sẵn sàng hoặc token đã hết hạn', $view);
        $this->assertStringContainsString("route('admin.invoices.create-token')", $view);
        $this->assertStringContainsString('Hệ thống sẽ không đưa tác vụ vào queue khi chưa có token hợp lệ.', $view);
    }
}
