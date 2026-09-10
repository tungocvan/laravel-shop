<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtQuickConnectModalContractTest extends TestCase
{
    #[Test]
    public function sync_page_uses_inline_gdt_connection_component(): void
    {
        $page = file_get_contents(base_path('Modules/Invoices/resources/views/pages/invoices/sync.blade.php'));

        $this->assertStringContainsString('<livewire:invoices.quick-gdt-connect />', $page);
        $this->assertStringNotContainsString("route('admin.invoices.create-token')", $page);
    }

    #[Test]
    public function quick_connect_loads_captcha_and_authenticates_without_navigation(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/QuickGdtConnect.php'));

        $this->assertStringContainsString('public function openModal(): void', $component);
        $this->assertStringContainsString('$this->refreshCaptcha();', $component);
        $this->assertStringContainsString('public function connect(): void', $component);
        $this->assertStringContainsString('$this->service->login(', $component);
        $this->assertStringContainsString('$this->modalOpen = false', $component);
        $this->assertStringContainsString('$this->dispatch(\'gdt-connected\')', $component);
        $this->assertStringNotContainsString('redirectRoute(', $component);
    }

    #[Test]
    public function modal_exposes_refresh_error_and_loading_states(): void
    {
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/quick-gdt-connect.blade.php'));

        $this->assertStringContainsString('Kết nối GDT', $view);
        $this->assertStringContainsString('Tải captcha mới', $view);
        $this->assertStringContainsString('wire:submit="connect"', $view);
        $this->assertStringContainsString('Đang xác thực GDT…', $view);
        $this->assertStringContainsString('{{ $error }}', $view);
        $this->assertStringContainsString('role="dialog"', $view);
    }
}
