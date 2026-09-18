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
        $this->assertStringContainsString("'response_context' => ", $service);
        $this->assertStringContainsString("'network_context' => \$transfer", $service);
        $this->assertStringContainsString("'primary_ip' =>", $service);
        $this->assertStringContainsString("'http_version' =>", $service);
        $this->assertStringContainsString("'ssl_verify_result' =>", $service);
        $this->assertStringContainsString("'request_id_present' =>", $service);
        $this->assertStringNotContainsString("'cookie_value' =>", $service);
        // Credentials and token necessarily appear in the outbound payload/cache path.
        // The safety boundary is that diagnostic/log contexts never include their values.
        $this->assertStringNotContainsString("'password' => \$password,", $this->diagnosticLoggingSection($service));
        $this->assertStringNotContainsString("'cvalue' => \$cvalue,", $this->diagnosticLoggingSection($service));
        $this->assertStringNotContainsString("'token' => \$token,", $this->diagnosticLoggingSection($service));
        $this->assertStringContainsString("'cache_store' => (string) config('cache.default')", $service);
        $this->assertStringContainsString("'cache_key' => \$cacheKey", $service);
        $this->assertStringContainsString("'cache_put_succeeded' => \$cachePutSucceeded", $service);
        $this->assertStringContainsString("'token_present_after_put' => \$tokenPresentAfterPut", $service);
        $this->assertStringContainsString("'token_ttl_seconds' => \$time", $service);
        $this->assertStringNotContainsString("'token_cached' => true", $service);
    }

    #[Test]
    public function authenticate_sends_a_fresh_request_id_without_logging_its_value(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtApiService.php'));

        $this->assertStringContainsString('$requestId = (string) Str::uuid();', $service);
        $this->assertStringContainsString("->withHeader('request-id', \$requestId)", $service);
        $this->assertStringContainsString("'request_id' => 'generated-per-auth-request'", $service);
        $this->assertStringNotContainsString("'request_id' => \$requestId", $service);
    }

    #[Test]
    public function invoice_query_rejection_diagnostics_distinguish_401_from_403_without_logging_token(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));

        $this->assertStringContainsString('GDT invoice query rejected.', $service);
        $this->assertStringContainsString("'authorization' => 'bearer-token-present'", $service);
        $this->assertStringContainsString("'request_id' => 'generated-per-query-request'", $service);
        $this->assertStringContainsString('if ($res->status() === 401)', $service);
        $this->assertStringContainsString("Cache::forget(config('invoices.gdt.cache_key'));", $service);
        $this->assertStringContainsString('GDT từ chối yêu cầu truy vấn hóa đơn (HTTP 403)', $service);
        $this->assertStringContainsString("'request-id' => (string) Str::uuid()", $service);
        $this->assertStringContainsString("'Origin' => ".'$origin', $service);
        $this->assertStringContainsString("'Referer' => ".'$origin'.".'/'", $service);
        $this->assertStringContainsString("'Action' => ''", $service);
        $this->assertStringContainsString("'End-Point' => '/'", $service);
        $this->assertStringNotContainsString("'token' => ".'$token', $service);
        $this->assertStringNotContainsString("'authorization' => ".'$token', $service);
    }

    #[Test]
    public function invoice_detail_request_context_distinguishes_401_from_403_without_logging_token(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtPdfService.php'));

        $this->assertStringContainsString('GDT invoice detail rejected.', $service);
        $this->assertStringContainsString("'request_id' => 'generated-per-detail-request'", $service);
        $this->assertStringContainsString("'request-id' => (string) Str::uuid()", $service);
        $this->assertStringContainsString("'Origin' => ".'$origin', $service);
        $this->assertStringContainsString("'Referer' => ".'$origin'.".'/'", $service);
        $this->assertStringContainsString("'Action' => ''", $service);
        $this->assertStringContainsString("'End-Point' => '/'", $service);
        $this->assertStringContainsString('if ('.'$response'.'->status() === 401)', $service);
        $this->assertStringContainsString('GDT từ chối yêu cầu lấy chi tiết hóa đơn (HTTP 403)', $service);
        $this->assertStringNotContainsString("'token' => ".'$token', $service);
        $this->assertStringNotContainsString("'authorization' => ".'$token', $service);
    }

    #[Test]
    public function local_cli_diagnostic_is_registered_and_does_not_print_secrets(): void
    {
        $provider = file_get_contents(base_path('Modules/Invoices/Providers/InvoicesServiceProvider.php'));
        $command = file_get_contents(base_path('Modules/Invoices/Console/Commands/DiagnoseGdtAuthenticationCommand.php'));

        $this->assertStringContainsString('DiagnoseGdtAuthenticationCommand::class', $provider);
        $this->assertStringContainsString("app()->environment('local')", $command);
        $this->assertStringContainsString("\$this->secret('Nhập mã captcha')", $command);
        $this->assertStringNotContainsString("config('invoices.gdt.password')", $command);
        $this->assertStringNotContainsString("config('invoices.gdt.username')", $command);
        $this->assertStringNotContainsString("'token' =>", $command);
        $this->assertStringNotContainsString("'cookie_value' =>", $command);
        $this->assertStringNotContainsString('$this->line($image)', $command);
        $this->assertStringContainsString("storage_path('app/invoices/gdt-diagnostics/captcha.svg')", $command);
        $this->assertStringContainsString('file_put_contents($captchaPath, $image)', $command);
    }

    #[Test]
    public function gdt_sync_log_distinguishes_api_count_from_requested_issued_date_coverage(): void
    {
        $service = file_get_contents(base_path('Modules/Invoices/Services/GdtInvoiceService.php'));

        $this->assertStringContainsString('[GDT] Tổng cộng API trả về:', $service);
        $this->assertStringContainsString('Theo ngày lập trong phạm vi', $service);
        $this->assertStringContainsString('ngoài phạm vi:', $service);
        $this->assertStringContainsString("Carbon::createFromFormat('d/m/Y'", $service);
    }

    private function diagnosticLoggingSection(string $service): string
    {
        $start = strpos($service, '$diagnostics = [');
        $end = strpos($service, 'private function responseMessage', $start ?: 0);

        $this->assertNotFalse($start);
        $this->assertNotFalse($end);

        return substr($service, $start, $end - $start);
    }
}
