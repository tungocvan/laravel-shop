<?php

namespace Tests\Feature\ClientPortal;

use Tests\TestCase;

class InvoicesGdtSmartSyncContractTest extends TestCase
{
    public function test_smart_sync_readiness_uses_database_latest_dates_per_direction(): void
    {
        $source = file_get_contents(base_path('Modules/Invoices/Services/GdtSyncReadinessService.php'));

        $this->assertStringContainsString('where(\'is_purchase\', $purchase)', $source);
        $this->assertStringContainsString('->max(\'invoice_date\')', $source);
        $this->assertStringContainsString("'suggested_start' => \$latestDate ?? \$yearStart->toDateString()", $source);
        $this->assertStringContainsString("'suggested_end' => \$today->toDateString()", $source);
    }

    public function test_pwa_sync_requires_server_side_gdt_token_before_dispatch(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Http/Controllers/InvoicesApplicationController.php'));

        $tokenGate = strpos($controller, 'if (! $readiness->hasToken())');
        $dispatch = strpos($controller, '$workspace->dispatchSync');

        $this->assertNotFalse($tokenGate);
        $this->assertNotFalse($dispatch);
        $this->assertLessThan($dispatch, $tokenGate);
        $this->assertStringContainsString('Phiên GDT đã hết hạn', $controller);
    }

    public function test_pwa_captcha_flow_keeps_challenge_key_in_server_session(): void
    {
        $controller = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/Http/Controllers/InvoicesApplicationController.php'));
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));

        $this->assertStringContainsString("session()->put('client.invoices.gdt.captcha_key'", $controller);
        $this->assertStringContainsString("session()->get('client.invoices.gdt.captcha_key')", $controller);
        $this->assertStringNotContainsString('name="ckey"', $view);
        $this->assertStringNotContainsString('GDT_API_PASSWORD', $view);
        $this->assertStringNotContainsString('accessToken', $view);
    }

    public function test_pwa_exposes_throttled_captcha_and_authentication_routes(): void
    {
        $routes = file_get_contents(base_path('Modules/ClientPortal/Applications/Invoices/routes.php'));

        $this->assertStringContainsString("'/sync/captcha'", $routes);
        $this->assertStringContainsString("'/sync/authenticate'", $routes);
        $this->assertStringContainsString("'throttle:10,1'", $routes);
    }

    public function test_sync_view_switches_suggested_start_between_purchase_and_sold(): void
    {
        $view = file_get_contents(base_path('Modules/ClientPortal/resources/views/applications/invoices/sync.blade.php'));

        $this->assertStringContainsString("'purchase' => \$purchase['suggested_start']", $view);
        $this->assertStringContainsString("'sold' => \$sold['suggested_start']", $view);
        $this->assertStringContainsString('Kết nối GDT để đồng bộ', $view);
        $this->assertStringContainsString('Local → Google Drive → GDT', $view);
    }
}
