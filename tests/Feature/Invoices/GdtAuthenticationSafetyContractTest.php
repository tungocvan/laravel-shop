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
        $this->assertStringContainsString('GDT từ chối yêu cầu xác thực của ứng dụng', $service);
        $this->assertStringContainsString("'Action' => ''", $service);
        $this->assertStringContainsString("'End-Point' => '/'", $service);
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
        $this->assertStringContainsString('GDT từ chối yêu cầu xác thực', $view);
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
        // Credentials and token necessarily appear in the outbound payload/cache path.
        // The safety boundary is that diagnostic/log contexts never include their values.
        $this->assertStringNotContainsString("'password' => \$password,", $this->diagnosticLoggingSection($service));
        $this->assertStringNotContainsString("'cvalue' => \$cvalue,", $this->diagnosticLoggingSection($service));
        $this->assertStringNotContainsString("'token' => \$token,", $this->diagnosticLoggingSection($service));
    }
    private function diagnosticLoggingSection(string $service): string
    {
        $start = strpos($service, "\$diagnostics = [");
        $end = strpos($service, 'private function responseMessage', $start ?: 0);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($service, $start, $end - $start);
    }
}
