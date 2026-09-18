<?php

namespace Tests\Feature\Invoices;

use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GdtAuthenticationSafetyContractTest extends TestCase
{
    #[Test]
    public function blocked_upstream_authentication_is_classified_without_browser_impersonation(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtApiService.php'));
        $config = file_get_contents(base_path('Modules/Invoices/config/invoices.php'));

        $this->assertStringContainsString("'code' => 'UPSTREAM_REQUEST_BLOCKED'", $service);
        $this->assertStringContainsString("'http_status' => 403", $service);
        $this->assertStringContainsString('GDT từ chối yêu cầu xác thực từ máy chủ', $service);
        $this->assertStringContainsString('Laravel-Invoices-GDT/1.0', $config);
        $this->assertStringNotContainsString('Chrome/', $config);
    }

    #[Test]
    public function blocked_authentication_does_not_immediately_refresh_captcha_and_ui_explains_next_step(): void
    {
        $component = file_get_contents(base_path('Modules/Invoices/Livewire/QuickGdtConnect.php'));
        $view = file_get_contents(base_path('Modules/Invoices/resources/views/livewire/quick-gdt-connect.blade.php'));

        $this->assertStringContainsString("if (\$this->errorCode !== 'UPSTREAM_REQUEST_BLOCKED')", $component);
        $this->assertStringContainsString('$this->refreshCaptchaKeepingError();', $component);
        $this->assertStringContainsString('GDT từ chối đăng nhập từ máy chủ', $view);
        $this->assertStringContainsString('không thử kết nối liên tục', $view);
        $this->assertStringContainsString('phương thức tích hợp chính thức', $view);
    }

    #[Test]
    public function diagnostics_are_metadata_only_and_do_not_log_authentication_secrets(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtApiService.php'));

        $this->assertStringContainsString("'cookie_metadata' => \$this->cookieMetadata(\$cookies)", $service);
        $this->assertStringContainsString("'request_context' => [", $service);
        $this->assertStringContainsString("'response_context' => [", $service);
        $this->assertStringNotContainsString("'cookie_value' =>", $service);
        $this->assertStringNotContainsString("'password' => \$password", $service);
        $this->assertStringNotContainsString("'captcha' => \$cvalue", $service);
        $this->assertStringNotContainsString("'token' => \$token", $service);
    }
}
